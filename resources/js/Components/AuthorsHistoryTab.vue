<template>
  <div id="authorsHistory" class="authors-history">
    <div id="historyHeader">
      <h2>{{ t("plugins.generic.authorsHistory.displayName") }}</h2>
    </div>
    <div id="historyBody">
      <p v-if="isLoading" class="authorPublications">{{ t("common.loading") }}</p>
      <p v-else-if="errorMessage" class="authorPublications">{{ errorMessage }}</p>
      <p v-else-if="authors.length === 0" class="authorPublications">
        {{ t("plugins.generic.authorsHistory.noPublications") }}
      </p>

      <template v-else>
        <div
          v-for="(author, authorIndex) in authors"
          :key="`${author.name}-${authorIndex}`"
          class="authorHistory"
        >
          <h3>{{ author.name }}</h3>
          <span v-if="author.correspondingAuthor">
            {{ t("submission.submit.selectPrincipalContact") }}
          </span>
          <br v-if="author.correspondingAuthor" />

          <a
            v-if="author.orcid"
            :href="author.orcid"
            target="_blank"
            rel="noopener noreferrer"
          >
            <strong>{{ t("plugins.generic.authorsHistory.orcid") }}:</strong>
            {{ author.orcid }}
          </a>
          <span v-else>{{ t("plugins.generic.authorsHistory.noORCID") }}</span>

          <br />
          <span>
            <strong>{{ t("email.email") }}:</strong> {{ author.email }}
          </span>

          <p v-if="author.submissions.length === 0" class="authorPublications">
            {{ t("plugins.generic.authorsHistory.noPublications") }}
          </p>
          <template v-else>
            <div class="authorPublications">
              <div
                v-for="sub in paginatedSubmissions(authorIndex)"
                :key="`${sub.id}-${sub.title}`"
                class="authorPublication"
              >
                <div class="submissionId">
                  <span>{{ sub.id }}</span>
                </div>
                <div class="submissionTitle">
                  <a
                    v-if="sub.urlWorkflow"
                    :href="sub.urlWorkflow"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {{ sub.title }}
                  </a>
                  <span v-else>{{ sub.title }}</span>
                </div>
                <div class="submissionStatus">
                  <a
                    v-if="sub.urlPublished"
                    :href="sub.urlPublished"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {{ sub.statusLabel }}
                  </a>
                  <span v-else>{{ sub.statusLabel }}</span>
                </div>
              </div>
            </div>

            <div
              v-if="totalPages(authorIndex) > 1"
              class="authorsHistoryPagination"
            >
              <span>{{ t("plugins.generic.authorsHistory.pages") }} &gt;&gt;</span>
              <button
                v-for="page in totalPages(authorIndex)"
                :key="`${authorIndex}-${page}`"
                class="pageButtons"
                :class="{ activePageButton: currentPages[authorIndex] === page }"
                type="button"
                @click="setPage(authorIndex, page)"
              >
                {{ page }}
              </button>
            </div>
          </template>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, reactive, ref, watch } from "vue";

const { useLocalize } = pkp.modules.useLocalize;
const { useUrl } = pkp.modules.useUrl;
const { t } = useLocalize();
const { apiUrl } = useUrl("submissions/authorsHistory");

const props = defineProps({
  submission: { type: Object, required: true },
});

const authors = ref([]);
const currentPages = reactive({});
const isLoading = ref(false);
const errorMessage = ref("");
const loadedSubmissionId = ref(null);

let activeRequestController = null;
let currentRequestId = 0;

const itemsPerPage = 10;

function getApiEndpointBaseUrl() {
  if (typeof apiUrl === "string") {
    return apiUrl;
  }

  if (apiUrl && typeof apiUrl.value === "string") {
    return apiUrl.value;
  }

  if (apiUrl?.value && typeof apiUrl.value.toString === "function") {
    return apiUrl.value.toString();
  }

  if (window.pkpAuthorsHistoryConfig?.apiEndpoint) {
    return window.pkpAuthorsHistoryConfig.apiEndpoint;
  }

  return null;
}

function buildEndpointUrl(submissionId) {
  const endpointBaseUrl = getApiEndpointBaseUrl();
  if (!endpointBaseUrl) {
    return null;
  }

  const endpointUrl = new URL(endpointBaseUrl, window.location.origin);
  endpointUrl.searchParams.set("submissionId", String(submissionId));
  return endpointUrl.toString();
}

function resetPagination() {
  Object.keys(currentPages).forEach((key) => {
    delete currentPages[key];
  });
  for (let i = 0; i < authors.value.length; i++) {
    currentPages[i] = 1;
  }
}

function totalPages(authorIndex) {
  const author = authors.value[authorIndex];
  if (!author?.submissions?.length) {
    return 0;
  }
  return Math.ceil(author.submissions.length / itemsPerPage);
}

function paginatedSubmissions(authorIndex) {
  const author = authors.value[authorIndex];
  if (!author?.submissions) {
    return [];
  }

  const page = currentPages[authorIndex] || 1;
  const start = (page - 1) * itemsPerPage;
  return author.submissions.slice(start, start + itemsPerPage);
}

function setPage(authorIndex, page) {
  if (page < 1 || page > totalPages(authorIndex)) {
    return;
  }
  currentPages[authorIndex] = page;
}

function normalizeAuthors(data) {
  if (!Array.isArray(data)) {
    return [];
  }

  return data.map((author) => {
    const submissions = Array.isArray(author.submissions) ? author.submissions : [];
    return {
      name: author.name || "",
      orcid: author.orcid || "",
      email: author.email || "",
      correspondingAuthor: Boolean(author.correspondingAuthor),
      submissions: submissions.map((submission) => ({
        id: submission.id,
        title: submission.title || "",
        statusLabel: submission.statusLabel || "",
        urlWorkflow: submission.urlWorkflow || null,
        urlPublished: submission.urlPublished || null,
      })),
    };
  });
}

async function fetchAuthorsHistory(submissionIdRaw) {
  const parsedSubmissionId = parseInt(submissionIdRaw, 10);

  if (!parsedSubmissionId || Number.isNaN(parsedSubmissionId)) {
    if (activeRequestController) {
      activeRequestController.abort();
      activeRequestController = null;
    }
    currentRequestId += 1;
    errorMessage.value = t("plugins.generic.authorsHistory.submissionIdError");
    authors.value = [];
    loadedSubmissionId.value = null;
    resetPagination();
    isLoading.value = false;
    return;
  }

  if (
    loadedSubmissionId.value === parsedSubmissionId &&
    authors.value.length > 0 &&
    !isLoading.value
  ) {
    return;
  }

  if (activeRequestController) {
    activeRequestController.abort();
    activeRequestController = null;
  }

  const endpointUrl = buildEndpointUrl(parsedSubmissionId);
  if (!endpointUrl) {
    errorMessage.value = t("plugins.generic.authorsHistory.loadError");
    authors.value = [];
    loadedSubmissionId.value = null;
    resetPagination();
    isLoading.value = false;
    return;
  }

  const requestController =
    typeof AbortController !== "undefined" ? new AbortController() : null;
  const requestId = currentRequestId + 1;
  currentRequestId = requestId;
  activeRequestController = requestController;

  isLoading.value = true;
  errorMessage.value = "";
  authors.value = [];
  resetPagination();

  try {
    const response = await fetch(endpointUrl, {
      credentials: "same-origin",
      method: "GET",
      signal: requestController?.signal,
    });

    if (!response.ok) {
      throw new Error("authorsHistory API load failed");
    }

    const data = await response.json();

    if (currentRequestId !== requestId) {
      return;
    }

    authors.value = normalizeAuthors(data);
    loadedSubmissionId.value = parsedSubmissionId;
    resetPagination();
  } catch (error) {
    if (error?.name === "AbortError") {
      return;
    }
    if (currentRequestId !== requestId) {
      return;
    }

    errorMessage.value = t("plugins.generic.authorsHistory.loadError");
    loadedSubmissionId.value = null;
    authors.value = [];
    resetPagination();
  } finally {
    if (currentRequestId !== requestId) {
      return;
    }

    isLoading.value = false;
    if (activeRequestController === requestController) {
      activeRequestController = null;
    }
  }
}

watch(
  () => props.submission?.id,
  (submissionId) => {
    fetchAuthorsHistory(submissionId);
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  if (activeRequestController) {
    activeRequestController.abort();
    activeRequestController = null;
  }
});
</script>

<style scoped>
#historyHeader {
  margin-top: 18px;
  margin-bottom: 18px;
}

.authorHistory {
  margin-bottom: 18px;
  background-color: #dcdcdc;
  padding: 8px;
  border-radius: 10px;
  position: relative;
}

.authorHistory h3 {
  margin-left: 10px;
  margin-bottom: 10px;
}

.authorHistory span,
.authorHistory a {
  margin-left: 18px;
}

.authorPublications {
  margin-top: 18px;
  background-color: #f5f5f5;
  border-radius: 8px;
  padding: 8px;
}

.authorPublication {
  display: flex;
  align-items: center;
  padding: 4px;
}

.authorPublication:not(:first-child) {
  border-top: 1px solid #dcdcdc;
}

.submissionId {
  width: 10%;
  text-align: center;
}

.submissionTitle {
  width: 80%;
  text-align: justify;
  line-height: 1.5em;
  margin-left: 8px;
  padding: 2px 4px;
}

.submissionStatus {
  width: 15%;
  text-align: center;
}

.submissionTitle span {
  margin: 0;
}

.submissionStatus span {
  margin: 0;
}

.pageButtons {
  font-weight: bold;
  border-radius: 5px;
  border-width: thin;
  background-color: #dcdcdc;
  width: 40px;
  height: 24px;
  margin-left: 4px;
}

.pageButtons.activePageButton {
  background-color: #006798;
  border-color: #006798;
  color: #fff;
}

.authorsHistoryPagination {
  margin-top: 10px;
  margin-left: 18px;
}

.authorsHistoryPagination span {
  margin-left: 0;
}
</style>

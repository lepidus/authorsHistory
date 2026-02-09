<template>
  <div class="authors-history">
    <div class="authors-history__header">
      <h3>{{ t("plugins.generic.authorsHistory.displayName") }}</h3>
    </div>

    <div v-if="isLoading" class="authors-history__loading">
      {{ t("common.loading") }}
    </div>

    <div v-else-if="authors.length === 0" class="authors-history__empty">
      {{ t("plugins.generic.authorsHistory.noPublications") }}
    </div>

    <div v-else class="authors-history__body">
      <div
        v-for="(author, index) in authors"
        :key="index"
        class="authors-history__author"
      >
        <h4 class="authors-history__author-name">{{ author.name }}</h4>

        <span
          v-if="author.correspondingAuthor"
          class="authors-history__badge"
        >
          {{ t("submission.submit.selectPrincipalContact") }}
        </span>

        <div class="authors-history__author-details">
          <span v-if="author.orcid">
            <strong>{{ t("plugins.generic.authorsHistory.orcid") }}:</strong>
            <a :href="author.orcid" target="_blank" rel="noopener noreferrer">
              {{ author.orcid }}
            </a>
          </span>
          <span v-else>
            {{ t("plugins.generic.authorsHistory.noORCID") }}
          </span>
        </div>

        <div class="authors-history__author-details">
          <strong>{{ t("email.email") }}:</strong> {{ author.email }}
        </div>

        <div
          v-if="author.submissions.length === 0"
          class="authors-history__no-submissions"
        >
          {{ t("plugins.generic.authorsHistory.noPublications") }}
        </div>

        <div v-else>
          <PkpTable>
            <PkpTableHeader>
              <PkpTableColumn>ID</PkpTableColumn>
              <PkpTableColumn>{{ t("common.title") }}</PkpTableColumn>
              <PkpTableColumn>{{ t("common.status") }}</PkpTableColumn>
            </PkpTableHeader>
            <PkpTableBody>
              <PkpTableRow
                v-for="sub in paginatedSubmissions(author)"
                :key="sub.id"
              >
                <PkpTableCell>{{ sub.id }}</PkpTableCell>
                <PkpTableCell>
                  <a
                    v-if="sub.urlWorkflow"
                    :href="sub.urlWorkflow"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {{ sub.title }}
                  </a>
                  <span v-else>{{ sub.title }}</span>
                </PkpTableCell>
                <PkpTableCell>
                  <a
                    v-if="sub.urlPublished"
                    :href="sub.urlPublished"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {{ sub.statusLabel }}
                  </a>
                  <span v-else>{{ sub.statusLabel }}</span>
                </PkpTableCell>
              </PkpTableRow>
            </PkpTableBody>
          </PkpTable>

          <div
            v-if="totalPages(author) > 1"
            class="authors-history__pagination"
          >
            {{ t("plugins.generic.authorsHistory.pages") }}
            <button
              v-for="page in totalPages(author)"
              :key="page"
              class="authors-history__page-btn"
              :class="{
                'authors-history__page-btn--active':
                  currentPages[index] === page,
              }"
              @click="currentPages[index] = page"
            >
              {{ page }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";

const { useLocalize } = pkp.modules.useLocalize;
const { t } = useLocalize();

const props = defineProps({
  submission: { type: Object, required: true },
});

const authors = ref([]);
const isLoading = ref(true);
const currentPages = reactive({});
const itemsPerPage = 10;

function paginatedSubmissions(author) {
  const index = authors.value.indexOf(author);
  const page = currentPages[index] || 1;
  const start = (page - 1) * itemsPerPage;
  return author.submissions.slice(start, start + itemsPerPage);
}

function totalPages(author) {
  return Math.ceil(author.submissions.length / itemsPerPage);
}

onMounted(async () => {
  const { useUrl } = pkp.modules.useUrl;
  const { useFetch } = pkp.modules.useFetch;

  const { apiUrl } = useUrl(
    `submissions/authorsHistory?submissionId=${props.submission.id}`
  );
  const { data, fetch } = useFetch(apiUrl);

  await fetch();

  if (data.value) {
    authors.value = data.value;
    authors.value.forEach((_, index) => {
      currentPages[index] = 1;
    });
  }

  isLoading.value = false;
});
</script>

<style scoped>
.authors-history__header {
  margin-top: 1rem;
  margin-bottom: 1rem;
}

.authors-history__author {
  margin-bottom: 1.5rem;
  background-color: #f0f0f0;
  padding: 1rem;
  border-radius: 0.5rem;
}

.authors-history__author-name {
  margin-bottom: 0.5rem;
}

.authors-history__badge {
  display: inline-block;
  background-color: #006798;
  color: #fff;
  padding: 0.15rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.85em;
  margin-bottom: 0.5rem;
}

.authors-history__author-details {
  margin-bottom: 0.25rem;
}

.authors-history__no-submissions {
  margin-top: 1rem;
  font-style: italic;
}

.authors-history__loading {
  padding: 2rem;
  text-align: center;
}

.authors-history__pagination {
  margin-top: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.authors-history__page-btn {
  font-weight: bold;
  border-radius: 0.25rem;
  border: 1px solid #ccc;
  background-color: #e0e0e0;
  min-width: 2rem;
  height: 1.5rem;
  cursor: pointer;
}

.authors-history__page-btn--active {
  background-color: #006798;
  color: #fff;
  border-color: #006798;
}
</style>

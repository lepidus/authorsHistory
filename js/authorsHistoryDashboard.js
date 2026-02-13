/*
 * Copyright (c) 2020-2023 Lepidus Tecnologia
 * Copyright (c) 2020-2023 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 */

(function () {
    var config = window.pkpAuthorsHistoryConfig || {};
    var COMPONENT_NAME = "AuthorsHistoryWorkflowPanel";
    var EDITORIAL_DASHBOARD_KEY = "editorialDashboard";
    var MENU_ITEM_KEY = "publication_authorsHistory";
    var MENU_ITEM_STATE_KEY = "authorsHistory";

    function getMenuTitle(label) {
        return "Publication: " + label;
    }

    function getApiEndpointBaseUrl() {
        if (
            window.pkp
            && window.pkp.modules
            && window.pkp.modules.useUrl
            && typeof window.pkp.modules.useUrl.useUrl === "function"
        ) {
            var urlTools = window.pkp.modules.useUrl.useUrl("submissions/authorsHistory");
            if (urlTools && urlTools.apiUrl) {
                if (typeof urlTools.apiUrl === "string") {
                    return urlTools.apiUrl;
                }

                if (
                    typeof urlTools.apiUrl === "object"
                    && urlTools.apiUrl !== null
                ) {
                    if (typeof urlTools.apiUrl.value === "string") {
                        return urlTools.apiUrl.value;
                    }

                    if (urlTools.apiUrl.value && typeof urlTools.apiUrl.value.toString === "function") {
                        return urlTools.apiUrl.value.toString();
                    }
                }
            }
        }

        if (config.apiEndpoint) {
            return config.apiEndpoint;
        }

        return null;
    }

    function buildEndpointUrl(submissionId) {
        var endpointBaseUrl = getApiEndpointBaseUrl();
        if (!endpointBaseUrl) {
            return null;
        }

        var endpointUrl = new URL(endpointBaseUrl, window.location.origin);
        endpointUrl.searchParams.set("submissionId", String(submissionId));
        return endpointUrl.toString();
    }

    function normalizeAuthors(rawAuthors) {
        if (!Array.isArray(rawAuthors)) {
            return [];
        }

        return rawAuthors.map(function (author) {
            var submissions = Array.isArray(author.submissions) ? author.submissions : [];

            return {
                name: author.name || "",
                orcid: author.orcid || "",
                email: author.email || "",
                correspondingAuthor: Boolean(author.correspondingAuthor),
                submissions: submissions.map(function (submission) {
                    return {
                        id: submission.id,
                        title: submission.title || "",
                        statusLabel: submission.statusLabel || "",
                        urlWorkflow: submission.urlWorkflow || null,
                        urlPublished: submission.urlPublished || null,
                    };
                }),
            };
        });
    }

    function registerWorkflowComponent() {
        if (window.pkp.registry.getComponent(COMPONENT_NAME)) {
            return;
        }

        window.pkp.registry.registerComponent(COMPONENT_NAME, {
            name: COMPONENT_NAME,
            props: {
                submissionId: {
                    required: true,
                    type: [Number, String],
                },
            },
            data: function () {
                return {
                    authors: [],
                    currentPages: {},
                    isLoading: false,
                    errorMessage: "",
                    loadedSubmissionId: null,
                    currentRequestId: 0,
                    activeRequestController: null,
                    itemsPerPage: 10,
                };
            },
            watch: {
                submissionId: function () {
                    this.fetchAuthorsHistory();
                },
            },
            mounted: function () {
                this.fetchAuthorsHistory();
            },
            beforeUnmount: function () {
                if (this.activeRequestController) {
                    this.activeRequestController.abort();
                    this.activeRequestController = null;
                }
            },
            methods: {
                resetPagination: function () {
                    this.currentPages = {};
                    for (var i = 0; i < this.authors.length; i++) {
                        this.currentPages[i] = 1;
                    }
                },
                totalPages: function (authorIndex) {
                    var author = this.authors[authorIndex];
                    if (!author || !Array.isArray(author.submissions) || author.submissions.length === 0) {
                        return 0;
                    }
                    return Math.ceil(author.submissions.length / this.itemsPerPage);
                },
                paginatedSubmissions: function (authorIndex) {
                    var author = this.authors[authorIndex];
                    if (!author || !Array.isArray(author.submissions)) {
                        return [];
                    }

                    var page = this.currentPages[authorIndex] || 1;
                    var start = (page - 1) * this.itemsPerPage;
                    return author.submissions.slice(start, start + this.itemsPerPage);
                },
                setPage: function (authorIndex, page) {
                    if (page < 1 || page > this.totalPages(authorIndex)) {
                        return;
                    }

                    this.currentPages[authorIndex] = page;
                },
                fetchAuthorsHistory: function () {
                    var parsedSubmissionId = parseInt(this.submissionId, 10);
                    if (!parsedSubmissionId || Number.isNaN(parsedSubmissionId)) {
                        if (this.activeRequestController) {
                            this.activeRequestController.abort();
                            this.activeRequestController = null;
                        }
                        this.currentRequestId += 1;
                        this.errorMessage = this.submissionIdErrorLabel;
                        this.authors = [];
                        this.loadedSubmissionId = null;
                        this.currentPages = {};
                        this.isLoading = false;
                        return;
                    }

                    if (this.loadedSubmissionId === parsedSubmissionId && this.authors.length && !this.isLoading) {
                        return;
                    }

                    if (this.activeRequestController) {
                        this.activeRequestController.abort();
                        this.activeRequestController = null;
                    }

                    var endpointUrl = buildEndpointUrl(parsedSubmissionId);
                    if (!endpointUrl) {
                        this.errorMessage = this.loadErrorLabel;
                        this.authors = [];
                        this.loadedSubmissionId = null;
                        this.currentPages = {};
                        this.isLoading = false;
                        return;
                    }

                    var requestController = typeof AbortController !== "undefined"
                        ? new AbortController()
                        : null;
                    var requestId = this.currentRequestId + 1;
                    this.currentRequestId = requestId;
                    this.activeRequestController = requestController;

                    this.isLoading = true;
                    this.errorMessage = "";
                    this.authors = [];
                    this.currentPages = {};

                    var component = this;
                    fetch(endpointUrl, {
                        credentials: "same-origin",
                        method: "GET",
                        signal: requestController ? requestController.signal : undefined,
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error("authorsHistory API load failed");
                            }

                            return response.json();
                        })
                        .then(function (data) {
                            if (component.currentRequestId !== requestId) {
                                return;
                            }

                            component.authors = normalizeAuthors(data);
                            component.loadedSubmissionId = parsedSubmissionId;
                            component.resetPagination();
                        })
                        .catch(function (error) {
                            if (error && error.name === "AbortError") {
                                return;
                            }
                            if (component.currentRequestId !== requestId) {
                                return;
                            }

                            component.errorMessage = component.loadErrorLabel;
                            component.loadedSubmissionId = null;
                            component.authors = [];
                            component.currentPages = {};
                        })
                        .finally(function () {
                            if (component.currentRequestId !== requestId) {
                                return;
                            }

                            component.isLoading = false;
                            if (component.activeRequestController === requestController) {
                                component.activeRequestController = null;
                            }
                        });
                },
            },
            template: `
                <div class="authors-history authorsHistoryWorkflow">
                    <div id="historyHeader">
                        <h2>{{ tabLabel }}</h2>
                    </div>
                    <div id="historyBody">
                        <p v-if="isLoading" class="authorPublications">{{ loadingLabel }}</p>
                        <p v-else-if="errorMessage" class="authorPublications">{{ errorMessage }}</p>
                        <p v-else-if="authors.length === 0" class="authorPublications">{{ noPublicationsLabel }}</p>
                        <template v-else>
                            <div
                                v-for="(author, authorIndex) in authors"
                                :key="authorIndex"
                                class="authorHistory"
                            >
                                <h3>{{ author.name }}</h3>
                                <span v-if="author.correspondingAuthor">{{ correspondingAuthorLabel }}</span>
                                <br v-if="author.correspondingAuthor" />

                                <a
                                    v-if="author.orcid"
                                    :href="author.orcid"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <strong>{{ orcidLabel }}:</strong> {{ author.orcid }}
                                </a>
                                <span v-else>{{ noOrcidLabel }}</span>

                                <br />
                                <span><strong>{{ emailLabel }}:</strong> {{ author.email }}</span>

                                <p
                                    v-if="author.submissions.length === 0"
                                    class="authorPublications"
                                >
                                    {{ noPublicationsLabel }}
                                </p>
                                <template v-else>
                                    <div class="authorPublications">
                                        <div
                                            v-for="sub in paginatedSubmissions(authorIndex)"
                                            :key="String(sub.id) + '-' + sub.title"
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
                                        <span>{{ pagesLabel }} &gt;&gt;</span>
                                        <button
                                            v-for="page in totalPages(authorIndex)"
                                            :key="page"
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
            `,
            computed: {
                tabLabel: function () {
                    return config.tabLabel || "Authors History";
                },
                loadingLabel: function () {
                    return config.loadingLabel || "Loading...";
                },
                loadErrorLabel: function () {
                    return config.loadErrorLabel || "Could not load authors history.";
                },
                submissionIdErrorLabel: function () {
                    return config.submissionIdErrorLabel || "Could not detect submission ID.";
                },
                noPublicationsLabel: function () {
                    return config.noPublicationsLabel || "No submissions found.";
                },
                noOrcidLabel: function () {
                    return config.noOrcidLabel || "No ORCID given.";
                },
                orcidLabel: function () {
                    return config.orcidLabel || "ORCID";
                },
                emailLabel: function () {
                    return config.emailLabel || "Email";
                },
                pagesLabel: function () {
                    return config.pagesLabel || "Page(s)";
                },
                correspondingAuthorLabel: function () {
                    return config.correspondingAuthorLabel || "Principal Contact";
                },
            },
        });
    }

    function applyWorkflowStoreExtensions(workflowStore) {
        if (!workflowStore || workflowStore.__authorsHistoryExtended) {
            return;
        }
        workflowStore.__authorsHistoryExtended = true;

        if (!workflowStore.extender) {
            return;
        }

        workflowStore.extender.extendFn("getMenuItems", function (menuItems, args) {
            if (!Array.isArray(menuItems)) {
                return menuItems;
            }

            var dashboardPage = (args && args.dashboardPage)
                || workflowStore.dashboardPage
                || (workflowStore.props && workflowStore.props.pageInitConfig
                    ? workflowStore.props.pageInitConfig.dashboardPage
                    : null);
            var isEditorialDashboard = dashboardPage === EDITORIAL_DASHBOARD_KEY;
            if (!isEditorialDashboard) {
                return menuItems;
            }

            if (args && args.permissions && args.permissions.canAccessPublication === false) {
                return menuItems;
            }

            return menuItems.map(function (menuItem) {
                if (menuItem.key !== "publication" || !Array.isArray(menuItem.items)) {
                    return menuItem;
                }

                var hasAuthorsHistory = menuItem.items.some(function (item) {
                    return item.key === MENU_ITEM_KEY;
                });
                if (hasAuthorsHistory) {
                    return menuItem;
                }

                var label = config.tabLabel || "Authors History";
                var authorsHistoryItem = {
                    key: MENU_ITEM_KEY,
                    label: label,
                    state: {
                        primaryMenuItem: "publication",
                        secondaryMenuItem: MENU_ITEM_STATE_KEY,
                        title: getMenuTitle(label),
                    },
                };

                return Object.assign({}, menuItem, {
                    items: menuItem.items.concat([authorsHistoryItem]),
                });
            });
        });

        workflowStore.extender.extendFn("getPrimaryItems", function (primaryItems, args) {
            var selectedMenuState = args && args.selectedMenuState ? args.selectedMenuState : null;
            if (
                !selectedMenuState
                || selectedMenuState.primaryMenuItem !== "publication"
                || selectedMenuState.secondaryMenuItem !== MENU_ITEM_STATE_KEY
            ) {
                return primaryItems;
            }

            var submission = args && args.submission ? args.submission : null;
            if (!submission || !submission.id) {
                return primaryItems;
            }

            return [
                {
                    component: COMPONENT_NAME,
                    props: {
                        submissionId: submission.id,
                    },
                },
            ];
        });
    }

    function extendWorkflowStore() {
        window.pkp.registry.storeExtend("workflow", function (piniaContext) {
            applyWorkflowStoreExtensions(piniaContext.store);
        });

        var piniaInstance = window.pkp.modules && window.pkp.modules.piniaInstance
            ? window.pkp.modules.piniaInstance
            : null;
        if (piniaInstance && piniaInstance._s && typeof piniaInstance._s.get === "function") {
            var existingWorkflowStore = piniaInstance._s.get("workflow");
            if (existingWorkflowStore) {
                applyWorkflowStoreExtensions(existingWorkflowStore);
            }
        }
    }

    function initWhenPkpReady() {
        if (!window.pkp || !window.pkp.registry || typeof window.pkp.registry.storeExtend !== "function") {
            window.setTimeout(initWhenPkpReady, 50);
            return;
        }

        if (window.__authorsHistoryWorkflowInitialized) {
            return;
        }
        window.__authorsHistoryWorkflowInitialized = true;

        registerWorkflowComponent();
        extendWorkflowStore();
    }

    initWhenPkpReady();
})();

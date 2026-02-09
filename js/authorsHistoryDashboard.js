/*
 * Copyright (c) 2020-2023 Lepidus Tecnologia
 * Copyright (c) 2020-2023 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 */

(function () {
    var config = window.pkpAuthorsHistoryConfig || {};
    if (!config.endpoint) {
        return;
    }

    var COMPONENT_NAME = "AuthorsHistoryWorkflowPanel";
    var EDITORIAL_DASHBOARD_KEY = "editorialDashboard";
    var MENU_ITEM_KEY = "publication_authorsHistory";
    var MENU_ITEM_STATE_KEY = "authorsHistory";

    function getMenuTitle(label) {
        return "Publication: " + label;
    }

    function initPagination(rootElement) {
        if (!window.AuthorsHistoryPagination || typeof window.AuthorsHistoryPagination.init !== "function") {
            return;
        }

        window.AuthorsHistoryPagination.init(rootElement.querySelector("#authorsHistory") || rootElement);
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
                    htmlContent: "",
                    isLoading: false,
                    errorMessage: "",
                    loadedSubmissionId: null,
                    currentRequestId: 0,
                    activeRequestController: null,
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
            methods: {
                fetchAuthorsHistory: function () {
                    var parsedSubmissionId = parseInt(this.submissionId, 10);
                    if (!parsedSubmissionId || Number.isNaN(parsedSubmissionId)) {
                        if (this.activeRequestController) {
                            this.activeRequestController.abort();
                            this.activeRequestController = null;
                        }
                        this.currentRequestId += 1;
                        this.errorMessage = config.submissionIdErrorLabel || "Could not detect submission ID.";
                        this.htmlContent = "";
                        this.loadedSubmissionId = null;
                        this.isLoading = false;
                        return;
                    }

                    if (this.loadedSubmissionId === parsedSubmissionId && this.htmlContent && !this.isLoading) {
                        return;
                    }

                    if (this.activeRequestController) {
                        this.activeRequestController.abort();
                        this.activeRequestController = null;
                    }

                    var requestController = typeof AbortController !== "undefined"
                        ? new AbortController()
                        : null;
                    var requestId = this.currentRequestId + 1;
                    this.currentRequestId = requestId;
                    this.activeRequestController = requestController;

                    this.isLoading = true;
                    this.errorMessage = "";
                    this.htmlContent = "";

                    var endpointUrl = new URL(config.endpoint, window.location.origin);
                    endpointUrl.searchParams.set("submissionId", String(parsedSubmissionId));

                    var component = this;
                    fetch(endpointUrl.toString(), {
                        credentials: "same-origin",
                        method: "GET",
                        signal: requestController ? requestController.signal : undefined,
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error("authorsHistory load failed");
                            }

                            return response.text();
                        })
                        .then(function (html) {
                            if (component.currentRequestId !== requestId) {
                                return;
                            }

                            component.htmlContent = html;
                            component.loadedSubmissionId = parsedSubmissionId;
                            component.$nextTick(function () {
                                initPagination(component.$el);
                            });
                        })
                        .catch(function (error) {
                            if (error && error.name === "AbortError") {
                                return;
                            }
                            if (component.currentRequestId !== requestId) {
                                return;
                            }

                            component.errorMessage = config.loadErrorLabel || "Could not load authors history.";
                            component.loadedSubmissionId = null;
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
            template:
                '<div class="authorsHistoryWorkflow">' +
                '  <p v-if="isLoading" class="authorPublications">{{ configLoadingLabel }}</p>' +
                '  <p v-else-if="errorMessage" class="authorPublications">{{ errorMessage }}</p>' +
                '  <div v-else class="authorsHistoryWorkflow__content" v-html="htmlContent"></div>' +
                "</div>",
            computed: {
                configLoadingLabel: function () {
                    return config.loadingLabel || "Loading...";
                },
            },
        });
    }

    function applyWorkflowStoreExtensions(workflowStore) {
            if (!workflowStore || workflowStore.__authorsHistoryExtended) {
                return;
            }
            workflowStore.__authorsHistoryExtended = true;

            if (!workflowStore || !workflowStore.extender) {
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

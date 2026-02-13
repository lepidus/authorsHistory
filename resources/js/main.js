import AuthorsHistoryTab from "./Components/AuthorsHistoryTab.vue";

const EDITORIAL_DASHBOARD_KEY = "editorialDashboard";
const MENU_ITEM_KEY = "publication_authorsHistory";
const MENU_ITEM_STATE_KEY = "authorsHistory";

pkp.registry.registerComponent("AuthorsHistoryTab", AuthorsHistoryTab);

function applyWorkflowStoreExtensions(workflowStore) {
  if (!workflowStore || workflowStore.__authorsHistoryExtended) {
    return;
  }

  workflowStore.__authorsHistoryExtended = true;

  workflowStore.extender.extendFn("getMenuItems", (menuItems, args) => {
    if (!Array.isArray(menuItems)) {
      return menuItems;
    }

    const dashboardPage =
      args?.dashboardPage ||
      workflowStore.dashboardPage ||
      workflowStore.props?.pageInitConfig?.dashboardPage;
    const isEditorialDashboard = dashboardPage === EDITORIAL_DASHBOARD_KEY;

    if (!isEditorialDashboard) {
      return menuItems;
    }

    if (args?.permissions?.canAccessPublication === false) {
      return menuItems;
    }

    const { useLocalize } = pkp.modules.useLocalize;
    const { t } = useLocalize();
    const label = t("plugins.generic.authorsHistory.displayName");

    return menuItems.map((menuItem) => {
      if (menuItem.key !== "publication" || !Array.isArray(menuItem.items)) {
        return menuItem;
      }

      const hasAuthorsHistory = menuItem.items.some(
        (item) => item.key === MENU_ITEM_KEY
      );

      if (hasAuthorsHistory) {
        return menuItem;
      }

      return {
        ...menuItem,
        items: [
          ...menuItem.items,
          {
            key: MENU_ITEM_KEY,
            label,
            state: {
              primaryMenuItem: "publication",
              secondaryMenuItem: MENU_ITEM_STATE_KEY,
              title: `Publication: ${label}`,
            },
          },
        ],
      };
    });
  });

  workflowStore.extender.extendFn("getPrimaryItems", (primaryItems, args) => {
    if (
      args?.selectedMenuState?.primaryMenuItem === "publication" &&
      args?.selectedMenuState?.secondaryMenuItem === MENU_ITEM_STATE_KEY
    ) {
      if (!args?.submission?.id) {
        return primaryItems;
      }

      return [
        {
          component: "AuthorsHistoryTab",
          props: { submission: args.submission },
        },
      ];
    }

    return primaryItems;
  });
}

pkp.registry.storeExtend("workflow", (piniaContext) => {
  applyWorkflowStoreExtensions(piniaContext.store);
});

const piniaInstance = pkp.modules?.piniaInstance;
if (piniaInstance?._s?.get) {
  const existingWorkflowStore = piniaInstance._s.get("workflow");
  if (existingWorkflowStore) {
    applyWorkflowStoreExtensions(existingWorkflowStore);
  }
}

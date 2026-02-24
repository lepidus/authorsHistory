import AuthorsHistoryTab from "./Components/AuthorsHistoryTab.vue";

pkp.registry.registerComponent("AuthorsHistoryTab", AuthorsHistoryTab);

pkp.registry.storeExtend("workflow", (piniaContext) => {
  const workflowStore = piniaContext.store;
  const { useLocalize } = pkp.modules.useLocalize;
  const { t } = useLocalize();

  workflowStore.extender.extendFn("getMenuItems", (menuItems) => {
    return menuItems.map((menuItem) => {
      if (menuItem.key === "publication" && menuItem.items) {
        return {
          ...menuItem,
          items: [
            ...menuItem.items,
            {
              key: "publication_authorsHistory",
              label: t("plugins.generic.authorsHistory.displayName"),
              state: {
                primaryMenuItem: "publication",
                secondaryMenuItem: "authorsHistory",
                title: t("plugins.generic.authorsHistory.displayName"),
              },
            },
          ],
        };
      }
      return menuItem;
    });
  });

  workflowStore.extender.extendFn("getPrimaryItems", (primaryItems, args) => {
    if (
      args?.selectedMenuState?.primaryMenuItem === "publication" &&
      args?.selectedMenuState?.secondaryMenuItem === "authorsHistory"
    ) {
      return [
        {
          component: "AuthorsHistoryTab",
          props: { submission: args.submission },
        },
      ];
    }
    return primaryItems;
  });
});

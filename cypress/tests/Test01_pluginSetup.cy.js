describe('Authors History - Plugin setup', function () {
    it('Enables Authors History plugin', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('nav').contains('Settings').click();
		cy.get('nav').contains('Website').click({ force: true });

		cy.waitJQuery();
		cy.get('button[id="plugins-button"]').click();

		cy.get('input[id^=select-cell-authorshistoryplugin]').check();
		cy.get('input[id^=select-cell-authorshistoryplugin]').should('be.checked');
    });
});

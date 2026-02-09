describe('Authors History - Plugin setup', function () {
    it('Enables Authors History plugin', function () {
		cy.login('dbarnes', null, 'publicknowledge');
		cy.visit('index.php/publicknowledge/management/settings/website');

		cy.waitJQuery();
		cy.get('button#plugins-button').click();

		cy.get('input[id^=select-cell-authorshistoryplugin]').check({force: true});
		cy.get('input[id^=select-cell-authorshistoryplugin]').should('be.checked');
    });
});

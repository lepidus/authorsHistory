describe('Authors History - Plugin setup', function () {
    it('Enables Authors History plugin', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('a:contains("Website")').click();

		cy.waitJQuery();
		cy.get('button#plugins-button').click();

		cy.get('input[id^=select-cell-authorshistoryplugin]').check();
		cy.get('input[id^=select-cell-authorshistoryplugin]').should('be.checked');
    });
});
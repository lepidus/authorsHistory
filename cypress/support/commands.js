Cypress.Commands.add('findSubmission', function(tab, title) {
	const viewName = tab === 'archive' ? 'Published' : 'Active submissions';
	cy.get('nav').contains(viewName).click();
	cy.contains('table tr', title)
		.contains('button', /^\s*View\s*$/)
		.scrollIntoView()
		.should('be.visible')
		.click({force: true});
});

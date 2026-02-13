describe('Authors History - Visual check on publication menu', function () {
	it('Captures publication menu for submission 1', function () {
		cy.login('dbarnes', null, 'publicknowledge');
		cy.visit('/index.php/publicknowledge/en/dashboard/editorial?currentViewId=published&workflowSubmissionId=1&workflowMenuKey=publication_titleAbstract');
		cy.window().then((win) => {
			expect(win.pkpAuthorsHistoryConfig, 'authors history config').to.exist;
			expect(win.pkpAuthorsHistoryConfig.apiEndpoint, 'authors history api endpoint').to.be.a('string').and.not.to.be.empty;
		});

		cy.get('[data-cy="active-modal"]').should('be.visible');
		cy.get('[data-cy="active-modal"] nav')
			.contains('a', 'Authors History')
			.scrollIntoView()
			.should('exist');
		cy.wait(1500);

		cy.screenshot('authors-history-publication-menu-submission-1');

		cy.get('[data-cy="active-modal"] nav').screenshot('authors-history-publication-menu-nav-submission-1');
	});

	it('Denies endpoint access to non-editorial user', function () {
		cy.login('zwoods', null, 'publicknowledge');
		cy.request({
			url: '/index.php/publicknowledge/api/v1/submissions/authorsHistory?submissionId=1',
			failOnStatusCode: false,
		}).its('status').should('be.oneOf', [403, 404]);
	});
});

describe('Authors History - Visual check on publication menu', function () {
	it('Denies endpoint access to non-editorial user', function () {
		cy.login('zwoods', null, 'publicknowledge');
		cy.request({
			url: '/index.php/publicknowledge/api/v1/submissions/authorsHistory?submissionId=1',
			failOnStatusCode: false,
		}).its('status').should('be.oneOf', [401]);
	});
});

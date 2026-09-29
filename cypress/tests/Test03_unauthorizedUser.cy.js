describe('Authors History - Visual check on publication menu', function () {
	it('Denies endpoint access to non-editorial user', function () {
		cy.login('zwoods', null, 'publicknowledge');
		cy.request({
			url: '/index.php/publicknowledge/api/v1/authorsHistory?submissionId=1',
			failOnStatusCode: false,
		}).its('status').should('be.oneOf', [401]);
	});
	it('Denies endpoint access to editor not assigned to the submission', function () {
		cy.login('minoue', null, 'publicknowledge');
		cy.request({
			url: '/index.php/publicknowledge/api/v1/authorsHistory?submissionId=1',
			failOnStatusCode: false,
		}).its('status').should('be.oneOf', [401]);
	});
});

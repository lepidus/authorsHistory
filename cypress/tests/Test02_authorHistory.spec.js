describe('Checks history for an author', function () {
    var submissionData;
    
    before(function() {
        submissionData = {
			title: 'The great gig in the sky',
            section: 'Articles',
            sectionId: 1,
			abstract: 'Money: share it fairly, but dont take a slice of my pie',
			keywords: [
				'money'
			],
            files: [
                {
                    'file': 'dummy.pdf',
                    'fileName': 'dummy.pdf',
                    'mimeType': 'application/pdf',
                    'genre': Cypress.env('defaultGenre')
                }
            ]
        }
    });

    it('Creates new submission for an author', function() {
        cy.login('zwoods', null, 'publicknowledge');
        cy.getCsrfToken();
        cy.window()
			.then(() => {
				return cy.createSubmissionWithApi(submissionData, this.csrfToken);
			})
			.then(xhr => {
				return cy.submitSubmissionWithApi(submissionData.id, this.csrfToken);
			});
    });
    it('Publishes new submission', function() {
        cy.findSubmissionAsEditor('dbarnes', null, 'Woods');
        
        if (Cypress.env('contextTitles').en_US !== 'Public Knowledge Preprint Server') {
            cy.recordEditorialDecision('Accept and Skip Review');
            cy.get('li.ui-state-active a:contains("Copyediting")');
            cy.get('button[id="publication-button"]').click();
            cy.get('div#publication button:contains("Schedule For Publication")').click();
            cy.wait(1000);
            
            cy.get('select[id="assignToIssue-issueId-control"]').select('1');
            cy.get('div[id^="assign-"] button:contains("Save")').click();
        } else {
			cy.get('#publication-button').click();
			cy.get('.pkpPublication > .pkpHeader > .pkpHeader__actions > .pkpButton').click();
		}

        cy.get('div.pkpWorkflow__publishModal button:contains("Publish"), .pkp_modal_panel button:contains("Post")').click();
        cy.logout();
    });
    it('Checks author history on previous submission', function() {
        if (Cypress.env('contextTitles').en_US !== 'Public Knowledge Preprint Server') {
            cy.findSubmissionAsEditor('dbarnes', null, 'Woods');
        } else {
            cy.login('dbarnes', null, 'publicknowledge');
            cy.get('button:contains("Archives")').click();
            cy.get('span:contains("View Woods")').eq(1).click({force: true});
        }
        cy.get('button[id="publication-button"]').click();
        cy.get('button[id="authorsHistory-button"]').click();
        cy.get('.submissionTitle').contains(submissionData.title);
        
        if (Cypress.env('contextTitles').en_US !== 'Public Knowledge Preprint Server') {
            cy.get('a:contains("Published")').first().invoke('removeAttr', 'target').click();
        } else {
            cy.get('a:contains("Published")').eq(1).invoke('removeAttr', 'target').click();
        }

        cy.get('h1:contains("' + submissionData.title + '")');
    });
});
import '../support/commands.js';

describe('Checks history for an author', function () {
    let submissionData;
    let previousAuthorSubmission = 'Finocchiaro: Arguments About Arguments';

    before(function() {
        submissionData = {
			title: 'The great gig in the sky',
            section: 'Articles',
            sectionId: 1,
			abstract: 'Money: share it fairly, but dont take a slice of my pie',
            author: {
                givenName: 'Roger',
                familyName: 'Waters',
                email: 'roger.waters@pinkfloyd.com',
                country: 'United Kingdom'
            },
            keywords: [
				'money'
			],
            files: [
                {
                    'file': 'dummy.pdf',
                    'fileName': 'dummy.pdf',
                    'mimeType': 'application/pdf',
                    'genre': ((Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server')) ? ('Article Text') : ('Preprint Text')
                }
            ]
        };
    });

    function beginSubmission() {
        cy.get('label:contains("English")').click();
        cy.setTinyMceContent('startSubmission-title-control', submissionData.title);

        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.get('label:contains("' + submissionData.section + '")').click();
        }

        cy.get('input[name="submissionRequirements"]').check();
        cy.get('input[name="privacyConsent"]').check();
        cy.contains('button', 'Begin Submission').click();
    }

    function detailsStep() {
        cy.setTinyMceContent('titleAbstract-abstract-control-en', submissionData.abstract);
        submissionData.keywords.forEach(keyword => {
            cy.get('#titleAbstract-keywords-control-en').type(keyword, {delay: 0});
            cy.get('#titleAbstract-keywords-control-en').type('{enter}', {delay: 0});
        });
        cy.contains('button', 'Continue').click();
    }

    function filesStep() {
        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.uploadSubmissionFiles(submissionData.files);
        } else  {
            cy.addSubmissionGalleys(submissionData.files);
        }
        cy.contains('button', 'Continue').click();
    }

    it('Creates new submission for an author', function() {
        cy.login('zwoods', null, 'publicknowledge');
        cy.get('a:contains("New Submission")').first().click();

        beginSubmission();
        detailsStep();
        filesStep();
        cy.contains('button', 'Continue').click();
        cy.contains('button', 'Continue').click();
        cy.contains('button', 'Submit').click();
        cy.get('div[role=dialog]').within(() => {
            cy.contains('button', 'Submit').click();
        });
    });
    it('Publishes new submission', function() {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.findSubmission('active', submissionData.title);

        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.clickDecision('Accept and Skip Review');
            cy.waitForEmailTemplateToBeLoaded('Notify Authors');
            cy.get('.decision__footer button').contains('Continue').click();
            cy.recordDecision('has been sent to the copyediting stage');
            cy.isActiveStageTab('Copyediting');

            cy.publish('1', 'Vol. 1 No. 2 (2014)');
        } else {
            cy.openWorkflowMenu('Title & Abstract');
            cy.get('button:contains("Post"):visible').click();
            cy.get('div:contains("All requirements have been met. Are you sure you want to post this?")');
            cy.get('[id^="publish"] button:contains("Post")').click();
		}

        cy.logout();
    });
    it('Checks author history on previous submission', function() {
        cy.login('dbarnes', null, 'publicknowledge');
        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.findSubmission('active', previousAuthorSubmission);
        } else {
            cy.findSubmission('archive', previousAuthorSubmission);
        }

        cy.openWorkflowMenu('Authors History');
        cy.get('.authors-history').contains('a', submissionData.title);

        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.get('.authors-history a:contains("Published")').first().invoke('removeAttr', 'target').click();
        } else {
            cy.get('.authors-history a:contains("Published")').eq(1).invoke('removeAttr', 'target').click();
        }

        cy.get('h1:contains("' + submissionData.title + '")');
    });
    it('Submission with new versions do not appear multiple times on history', function() {
        cy.login('dbarnes', null, 'publicknowledge');

        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.findSubmission('active', previousAuthorSubmission);
        } else {
            cy.findSubmission('archive', previousAuthorSubmission);
        }
        cy.openWorkflowMenu('Authors History');
        cy.get('.authors-history').contains('a', submissionData.title);
        cy.get('.authors-history a:contains("' + submissionData.title + '")').its('length').then((initialCount) => {
            cy.visit('index.php/publicknowledge/dashboard/editorial');
            cy.findSubmission('archive', submissionData.title);

            cy.openWorkflowMenu('Title & Abstract');
            cy.contains('button', 'Create New Version').click();
            cy.get('div[role=dialog]:contains("Create New Version")').get('button').contains('Yes').click();
            cy.wait(2000);

            cy.openWorkflowMenu('Title & Abstract');

            if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
                cy.get('button:contains("Publish")').click();
                cy.get('div.pkpWorkflow__publishModal button:contains("Publish")').click();
            } else {
                cy.get('button').contains('Post').click();
                cy.contains('All requirements have been met.');
                cy.get('.pkpWorkflow__publishModal button').contains('Post').click();
            }

            cy.visit('index.php/publicknowledge/dashboard/editorial');
            if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
                cy.findSubmission('active', previousAuthorSubmission);
            } else {
                cy.findSubmission('archive', previousAuthorSubmission);
            }
            cy.openWorkflowMenu('Authors History');
            cy.get('.authors-history a:contains("' + submissionData.title + '")').should('have.length', initialCount);
        });
    });
});

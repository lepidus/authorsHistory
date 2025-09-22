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

    function uploadSubmissionFiles(files) {
        cy.intercept("POST", /submissions\/\d+\/files$/).as('fileUploaded');
        cy.intercept("POST", /submissions\/\d+\/files\/\d+/).as('genreDefined');
    
        files.forEach(file => {
            cy.fixture(file.file, 'base64').then(fileContent => {
                cy.get('input[type=file]').attachFile(
                    {
                        fileContent,
                        encoding: 'base64',
                        filePath: file.fileName,
                        mimeType: file.mimeType,
                    }
                );
                cy.wait('@fileUploaded').then(({response}) => {
                    expect(response.statusCode).to.eq(200)
                });
                cy.contains('button', file.genre).last().click({force: true});
                cy.wait('@genreDefined').then(({response}) => {
                    expect(response.statusCode).to.eq(200)
                });
    
                cy.contains('What kind of file is this?').should('not.exist');
                cy.contains('.listPanel__item', file.fileName);
                cy.contains('.pkpBadge', file.genre);
            });
        });
    }

    function beginSubmission() {
        cy.get('input[name="locale"][value="en"]').click();
        cy.setTinyMceContent('startSubmission-title-control', submissionData.title);
        
        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.get('input[name="sectionId"][value="1"]').click();
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
            uploadSubmissionFiles(submissionData.files);
        } else  {
            cy.addSubmissionGalleys(submissionData.files);
        }
        cy.contains('button', 'Continue').click();
    }

    it('Creates new submission for an author', function() {
        cy.login('zwoods', null, 'publicknowledge');
        cy.get('div#myQueue a:contains("New Submission")').click();
        
        beginSubmission();
        detailsStep();
        filesStep();
        cy.contains('button', 'Continue').click();
        cy.contains('button', 'Continue').click();
        cy.contains('button', 'Submit').click();
        cy.get('.modal__panel:visible').within(() => {
            cy.contains('button', 'Submit').click();
        });
    });
    it('Publishes new submission', function() {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.findSubmission('active', submissionData.title);
        
        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.get('li a:contains("Accept and Skip Review")').click();
            cy.contains('button', 'Skip this email').click();
            cy.contains('button', 'Record Decision').click();
            cy.contains('a', 'View Submission').click();
            cy.get('li.ui-state-active a:contains("Copyediting")');
            cy.get('#publication-button').click();
            cy.get('div#publication button:contains("Schedule For Publication")').click();
            cy.wait(1000);
            
            cy.get('select[id="assignToIssue-issueId-control"]').select('1');
            cy.get('div[id^="assign-"] button:contains("Save")').click();
        } else {
			cy.get('#publication-button').click();
			cy.get('div#publication button:contains("Post")').click();
		}

        cy.get('div.pkpWorkflow__publishModal button:contains("Publish"), .pkp_modal_panel button:contains("Post")').click();
        cy.logout();
    });
    it('Checks author history on previous submission', function() {
        cy.login('dbarnes', null, 'publicknowledge');
        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.findSubmission('active', previousAuthorSubmission);
        } else {
            cy.findSubmission('archive', previousAuthorSubmission);
        }

        cy.get('#publication-button').click();
        cy.get('#authorsHistory-button').click();
        cy.get('.submissionTitle').contains(submissionData.title);
        
        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.get('a:contains("Published")').first().invoke('removeAttr', 'target').click();
        } else {
            cy.get('a:contains("Published")').eq(1).invoke('removeAttr', 'target').click();
        }

        cy.get('h1:contains("' + submissionData.title + '")');
    });
    it('Submission with new versions do not appear multiple times on history', function() {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.findSubmission('archive', submissionData.title);

        cy.get('#publication-button').click();
        cy.contains('button', 'Create New Version').click();
        cy.get('.modal__panel button:contains("Yes")').click();
        cy.wait(2000);

        cy.get('.pkpPublication__version:contains("2")');
        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.get('div#publication button:contains("Publish")').click();
        } else {
			cy.get('div#publication button:contains("Post")').click();
		}
        cy.get('div.pkpWorkflow__publishModal button:contains("Publish"), .pkp_modal_panel button:contains("Post")').click();

        cy.contains('.app__navItem', 'Submissions').click();
        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.findSubmission('active', previousAuthorSubmission);
        } else {
            cy.findSubmission('archive', previousAuthorSubmission);
        }
        cy.get('#publication-button').click();
        cy.get('#authorsHistory-button').click();
        cy.get('a:contains("' + submissionData.title + '")').should('have.length', 1);
    });
});
import '../support/commands.js';

describe('Checks history for an author', function () {
    let submissionData;
    let createdSubmissionId = null;
    const previousAuthorSubmissionId = 19; // Seed data: "Finocchiaro: Arguments About Arguments"
    
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

    function getSubmissionIdFromLocation(location) {
        const pathMatch = location.pathname && location.pathname.match(/\/workflow\/access\/(\d+)/);
        if (pathMatch) {
            return parseInt(pathMatch[1], 10);
        }

        const searchParams = new URLSearchParams(location.search || '');
        const workflowSubmissionId = searchParams.get('workflowSubmissionId');
        if (workflowSubmissionId) {
            return parseInt(workflowSubmissionId, 10);
        }

        const submissionId = searchParams.get('submissionId');
        if (submissionId) {
            return parseInt(submissionId, 10);
        }

        return null;
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
        cy.get('body').then(($body) => {
            const confirmSubmitButton = $body.find('div[role="dialog"] button:contains("Submit")');
            if (confirmSubmitButton.length) {
                cy.wrap(confirmSubmitButton.first()).click();
            }
        });

        cy.contains('a', 'Review this submission').click();
        cy.location().then((location) => {
            createdSubmissionId = getSubmissionIdFromLocation(location);
            expect(createdSubmissionId, 'createdSubmissionId').to.be.a('number').and.to.be.greaterThan(0);
        });
    });
    it('Publishes new submission', function() {
        cy.then(() => {
            expect(createdSubmissionId, 'created submission id from previous test').to.be.a('number').and.to.be.greaterThan(0);
        });

        cy.login('dbarnes', null, 'publicknowledge');
        cy.visit('/index.php/publicknowledge/workflow/access/' + createdSubmissionId);
        
        if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
            cy.contains('button', 'Accept and Skip Review').click();
            cy.get('body').then(($body) => {
                const skipEmailButton = $body.find('button:contains("Skip this email")');
                if (skipEmailButton.length) {
                    cy.wrap(skipEmailButton.first()).click();
                }
            });
            cy.contains('button', 'Record Decision').click();
            cy.contains('a', 'View Submission').click();
            cy.openWorkflowMenu('Title & Abstract');

            cy.get('body').then(($body) => {
                const scheduleButton = $body.find('button:contains("Schedule For Publication")');
                if (scheduleButton.length) {
                    cy.wrap(scheduleButton.first()).click();
                    cy.wait(500);
                    cy.get('select[id="assignToIssue-issueId-control"]').select('1');
                    cy.get('div[id^="assign-"] button:contains("Save")').click();
                }
            });
        } else {
			cy.openWorkflowMenu('Title & Abstract');
            cy.contains('button', 'Post').click();
		}

        cy.get('body').then(($body) => {
            const publishButton = $body.find('button:contains("Publish"), button:contains("Post")');
            if (publishButton.length) {
                cy.wrap(publishButton.first()).click();
            }
        });
        cy.get('body').then(($body) => {
            const confirmPublishButton = $body.find('div.pkpWorkflow__publishModal button:contains("Publish"), .pkp_modal_panel button:contains("Post"), div[role="dialog"] button:contains("Publish"), div[role="dialog"] button:contains("Post")');
            if (confirmPublishButton.length) {
                cy.wrap(confirmPublishButton.first()).click();
            }
        });
        cy.logout();
    });
    it('Checks author history on previous submission', function() {
        cy.then(() => {
            expect(createdSubmissionId, 'created submission id from previous test').to.be.a('number').and.to.be.greaterThan(0);
        });

        cy.login('dbarnes', null, 'publicknowledge');
        cy.visit('/index.php/publicknowledge/workflow/access/' + previousAuthorSubmissionId);
        cy.get('[data-cy="active-modal"] nav').contains('a', 'Authors History').click();
        cy.get('[data-cy="active-modal"] h2').contains('Authors History');

        cy.get('.submissionTitle').contains(submissionData.title);

        cy.get('.authorPublication').should(($rows) => {
            const hasCreatedSubmission = Array.from($rows).some((row) => {
                const idText = row.querySelector('.submissionId span')?.textContent?.trim();
                return idText === String(createdSubmissionId);
            });
            expect(hasCreatedSubmission, 'history contains created submission').to.eq(true);
        });

        cy.get('.authorPublication').then(($rows) => {
            const targetRow = Array.from($rows).find((row) => {
                const idText = row.querySelector('.submissionId span')?.textContent?.trim();
                return idText === String(createdSubmissionId);
            });

            cy.wrap(targetRow)
                .find('.submissionTitle a')
                .invoke('removeAttr', 'target')
                .click({force: true});
        });

        cy.url().should('include', 'workflowSubmissionId=' + createdSubmissionId);
        cy.get('[data-cy="active-modal"]').should('be.visible');
    });
    it('Submission with new versions do not appear multiple times on history', function() {
        cy.then(() => {
            expect(createdSubmissionId, 'created submission id from previous test').to.be.a('number').and.to.be.greaterThan(0);
        });

        cy.login('dbarnes', null, 'publicknowledge');
        cy.visit('/index.php/publicknowledge/workflow/access/' + createdSubmissionId);

        cy.openWorkflowMenu('Title & Abstract');
        cy.get('body').then(($body) => {
            const createNewVersionButton = $body.find('button:contains("Create New Version")');
            if (createNewVersionButton.length) {
                cy.wrap(createNewVersionButton.first()).click();
                cy.get('div[role=dialog] button:contains("Yes")').click();
            }
        });

        cy.get('body').then(($body) => {
            const publishButton = $body.find('button:contains("Publish"), button:contains("Post")');
            if (publishButton.length) {
                cy.wrap(publishButton.first()).click();
            }
        });
        cy.get('body').then(($body) => {
            const confirmPublishButton = $body.find('div.pkpWorkflow__publishModal button:contains("Publish"), .pkp_modal_panel button:contains("Post"), div[role="dialog"] button:contains("Publish"), div[role="dialog"] button:contains("Post")');
            if (confirmPublishButton.length) {
                cy.wrap(confirmPublishButton.first()).click();
            }
        });

        cy.visit('/index.php/publicknowledge/workflow/access/' + previousAuthorSubmissionId);
        cy.get('[data-cy="active-modal"] nav').contains('a', 'Authors History').click();

        cy.get('.authorPublication .submissionId span').then(($ids) => {
            const matchingIds = Array.from($ids).filter((idEl) => {
                return idEl.textContent.trim() === String(createdSubmissionId);
            });
            expect(matchingIds.length, 'created submission appears once in history').to.eq(1);
        });
    });
});

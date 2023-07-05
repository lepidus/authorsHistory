describe('Checks history for an author', function () {
    let submissionData;
    
    before(function() {
        submissionData = {
            section: 'Articles',
			title: 'The great gig in the sky',
			abstract: 'Money: share it fairly, but dont take a slice of my pie',
			keywords: [
				'money'
			]
        }
    });

    function step1() {
        if (Cypress.env('contextTitles').en_US == 'Journal of Public Knowledge') {
			cy.get('select[id="sectionId"],select[id="seriesId"]').select(submissionData.section);
		}
        cy.get('input[id^="checklist-"]').click({ multiple: true });
		cy.get('input[id=privacyConsent]').click();
		cy.get('button.submitFormButton').click();
    }

    function step2() {
        cy.get('#submitStep2Form button.submitFormButton').click();
    }

    function step3() {
        cy.get('input[name^="title"]').first().type(submissionData.title, { delay: 0 });
        cy.get('label').contains('Title').click();
        cy.get('textarea[id^="abstract-"').then((node) => {
            cy.setTinyMceContent(node.attr("id"), submissionData.abstract);
        });
        cy.get('.section > label:visible').first().click();
        cy.get('ul[id^="en_US-keywords-"]').then(node => {
            node.tagit('createTag', submissionData.keywords[0]);
        });

        cy.get('#submitStep3Form button.submitFormButton').click();
    }

    function step4() {
        cy.waitJQuery();
		cy.get('#submitStep4Form button.submitFormButton').click();
		cy.get('button.pkpModalConfirmButton').click();
    }

    it('Creates new submission for an author', function() {
        cy.login('zwoods', null, 'publicknowledge');
        cy.get('div#myQueue a:contains("New Submission")').click();
        
        step1();
        step2();
        step3();
        step4();

        cy.waitJQuery();
		cy.get('h2:contains("Submission complete")');
		cy.logout();
    });
    it('Publishes new submission', function() {
        cy.findSubmissionAsEditor('dbarnes', null, 'Woods');
        
        if (Cypress.env('contextTitles').en_US !== 'Public Knowledge Preprint Server') {
            cy.recordEditorialDecision('Accept and Skip Review');
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
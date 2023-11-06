<?php

use PKP\tests\DatabaseTestCase;
use APP\facades\Repo;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\author\Author;
use PKP\db\DAORegistry;
use APP\journal\JournalDAO;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryDAO;

class AuthorsHistoryDAOTest extends DatabaseTestCase
{
    private $givenName = "Yves Saint Laurent";
    private $familyName = "Design";
    private $email = "yves.SL@naoexiste.com.br";
    private $affiliation = "Lepidus Tecnologia";
    private $locale = "pt_BR";
    private $submissionId;
    private $authorId;

    public function setUp(): void
    {
        parent::setUp();
        $this->authorId = $this->createAuthor();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $submission = Repo::submission()->get($this->submissionId);
        Repo::submission()->delete($submission);
    }

    private function createAuthor()
    {
        $contextId = 1;
        $context = DAORegistry::getDAO('JournalDAO')->getById($contextId);

        $submission = new Submission();
        $submission->setData('contextId', $contextId);
        $publication = new Publication();

        $this->submissionId = Repo::submission()->add($submission, $publication, $context);
        $submission = Repo::submission()->get($this->submissionId);
        $publication = $submission->getCurrentPublication();

        $author = new Author();
        $author->setData('publicationId', $publication->getId());
        $author->setGivenName($this->givenName, $this->locale);
        $author->setFamilyName($this->familyName, $this->locale);
        $author->setAffiliation($this->affiliation, $this->locale);
        $author->setEmail($this->email);
        $authorId = Repo::author()->add($author);

        return $authorId;
    }

    public function testAuthorIdRetrievingByGivenNameAndEmail()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedValidationResult = [$this->authorId];
        $validationResult = $authorsHistoryDAO->getAuthorIdByGivenNameAndEmail($this->givenName, $this->email);
        $this->assertEquals($expectedValidationResult, $validationResult);
    }
}

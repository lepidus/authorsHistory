<?php

use PKP\tests\DatabaseTestCase;
use APP\facades\Repo;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\author\Author;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryDAO;

class AuthorsHistoryDAOTest extends DatabaseTestCase
{
    private $authors;
    private $submission;
    private $locale = "pt_BR";
    private $testAuthorsData = [
        [
            'givenName' => 'Yves Saint Laurent',
            'familyName' => 'Design',
            'affiliation' => 'Lepidus Tecnologia',
            'email' => 'yves.SL@naoexiste.com.br',
            'orcid' => '0000-0002-1234-5678'
        ],
        [
            'givenName' => 'Coco Chanel',
            'familyName' => 'Fashion',
            'affiliation' => 'Chanel S.A.',
            'email' => 'coco.chanel@naoexiste.com.br',
            'orcid' => '0000-0002-1234-5678'
        ],
        [
            'givenName' => 'Giorgio Armani',
            'familyName' => 'Luxury',
            'affiliation' => 'Armani Group',
            'email' => 'yves.SL@naoexiste.com.br',
            'orcid' => '0000-0002-3456-7890'
        ]
    ];


    public function setUp(): void
    {
        parent::setUp();
        $this->submission = $this->createTestSubmission();
        $this->authors = $this->createTestAuthors();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Repo::submission()->delete($this->submission);
    }

    private function createTestSubmission(): Submission
    {
        $contextId = 1;
        $context = DAORegistry::getDAO('JournalDAO')->getById($contextId);

        $submission = new Submission();
        $submission->setData('contextId', $contextId);
        $publication = new Publication();

        $this->submissionId = Repo::submission()->add($submission, $publication, $context);
        $submission = Repo::submission()->get($this->submissionId);

        return $submission;
    }

    private function createTestAuthors(): array
    {
        $publication = $this->submission->getCurrentPublication();
        $authors = [];

        foreach ($this->testAuthorsData as $authorData) {
            $authors[] = $this->createAuthor($publication, $authorData);
        }

        return $authors;
    }

    private function createAuthor(Publication $publication, array $authorData): int
    {
        $author = new Author();
        $author->setData('publicationId', $publication->getId());
        $author->setGivenName($authorData['givenName'], $this->locale);
        $author->setFamilyName($authorData['familyName'], $this->locale);
        $author->setAffiliation($authorData['affiliation'], $this->locale);
        $author->setEmail($authorData['email'] ?? null);
        $author->setOrcid($authorData['orcid'] ?? null);

        return (int) Repo::author()->add($author);
    }

    public function testRetrieveAuthorsByEmail()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedAuthors = [$this->authors[0], $this->authors[2]];
        $retrievedAuthors = $authorsHistoryDAO->getAuthorsByEmail($this->testAuthorsData[0]['email']);

        $this->assertEquals($expectedAuthors, $retrievedAuthors);
    }

    public function testRetrieveAuthorsByOrcid()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedAuthors = [$this->authors[0], $this->authors[1]];
        $retrievedAuthors = $authorsHistoryDAO->getAuthorsByOrcid($this->testAuthorsData[0]['orcid']);

        $this->assertEquals($expectedAuthors, $retrievedAuthors);
    }

    public function testRetrieveAuthorsByGivenNameAndEmail()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedAuthors = [$this->authors[0]];
        $retrievedAuthors = $authorsHistoryDAO->getAuthorIdByGivenNameAndEmail($this->testAuthorsData[0]['givenName'], $this->testAuthorsData[0]['email']);

        $this->assertEquals($expectedAuthors, $retrievedAuthors);
    }

    public function testRetrieveSimilarAuthors()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedAuthors = $this->authors;
        $retrievedAuthors = $authorsHistoryDAO->getSimilarAuthors(
            $this->testAuthorsData[0]['email'],
            $this->testAuthorsData[0]['orcid'],
            $this->testAuthorsData[0]['givenName'],
            10
        );
        $retrievedAuthors = array_values($retrievedAuthors);
        sort($retrievedAuthors, SORT_NUMERIC);

        $this->assertEquals($expectedAuthors, $retrievedAuthors);
    }

    public function testRetrieveSimilarAuthorsWithNullEmail()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedAuthors = [$this->authors[0], $this->authors[1]];
        $retrievedAuthors = $authorsHistoryDAO->getSimilarAuthors(
            null,
            $this->testAuthorsData[0]['orcid'],
            $this->testAuthorsData[0]['givenName'],
            10
        );
        $retrievedAuthors = array_values($retrievedAuthors);
        sort($retrievedAuthors, SORT_NUMERIC);

        $this->assertEquals($expectedAuthors, $retrievedAuthors);
    }
}

<?php

use PKP\tests\DatabaseTestCase;
use APP\facades\Repo;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\author\Author;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryDAO;

class AuthorsHistoryDAOTest extends DatabaseTestCase
{
    private $contextId = 1;
    private $submission;
    private $authors;
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
        $context = DAORegistry::getDAO('JournalDAO')->getById($this->contextId);

        $submission = new Submission();
        $submission->setData('contextId', $this->contextId);
        $submission->setData('submissionProgress', '');
        $publication = new Publication();

        $submissionId = Repo::submission()->add($submission, $publication, $context);
        $submission = Repo::submission()->get($submissionId);

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

    private function mapExpectedAuthors(array $authors)
    {
        $mappedAuthors = [];
        foreach ($authors as $authorId) {
            $mappedAuthors[$authorId] = [
                'author_id' => $authorId,
                'submission_id' => $this->submission->getId()
            ];
        }

        return $mappedAuthors;
    }

    public function testRetrieveAuthorsByEmail()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedAuthors = $this->mapExpectedAuthors([$this->authors[0], $this->authors[2]]);
        $retrievedAuthors = $authorsHistoryDAO->getSimilarAuthorsByEmail($this->testAuthorsData[0]['email'], $this->contextId, true);

        $this->assertEquals($expectedAuthors, $retrievedAuthors);
    }

    public function testRetrieveAuthorsByOrcid()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedAuthors = $this->mapExpectedAuthors([$this->authors[0], $this->authors[1]]);
        $retrievedAuthors = $authorsHistoryDAO->getSimilarAuthorsByOrcid($this->testAuthorsData[0]['orcid'], $this->contextId);

        $this->assertEquals($expectedAuthors, $retrievedAuthors);
    }

    public function testRetrieveAuthorsByGivenNameAndEmail()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedAuthors = $this->mapExpectedAuthors([$this->authors[0]]);
        $retrievedAuthors = $authorsHistoryDAO->getSimilarAuthorsByGivenNameAndEmail(
            $this->testAuthorsData[0]['givenName'],
            $this->testAuthorsData[0]['email'],
            $this->contextId
        );

        $this->assertEquals($expectedAuthors, $retrievedAuthors);
    }

    public function testRetrieveSimilarAuthors()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedAuthors = $this->mapExpectedAuthors($this->authors);
        $retrievedAuthors = $authorsHistoryDAO->getSimilarAuthors(
            $this->contextId,
            $this->testAuthorsData[0]['email'],
            $this->testAuthorsData[0]['orcid'],
            $this->testAuthorsData[0]['givenName'],
            10
        );

        $this->assertEquals($expectedAuthors, $retrievedAuthors);
    }

    public function testRetrieveSimilarAuthorsWithNullEmail()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedAuthors =  $this->mapExpectedAuthors([$this->authors[0], $this->authors[1]]);
        $retrievedAuthors = $authorsHistoryDAO->getSimilarAuthors(
            $this->contextId,
            null,
            $this->testAuthorsData[0]['orcid'],
            $this->testAuthorsData[0]['givenName'],
            10
        );

        $this->assertEquals($expectedAuthors, $retrievedAuthors);
    }
}

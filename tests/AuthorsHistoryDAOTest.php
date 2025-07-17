<?php

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.services.PKPSchemaService'); // SCHEMA_ constants
import('classes.article.Author');
import('plugins.generic.authorsHistory.classes.AuthorsHistoryDAO');

class AuthorsHistoryDAOTest extends DatabaseTestCase
{
    private $authors;
    private $locale = 'pt_BR';
    private $testAuthorsData = [
        [
            'givenName' => 'Yves Saint Laurent',
            'familyName' => 'Design',
            'affiliation' => 'Lepidus Tecnologia',
            'email' => 'yves.SL@naoexiste.com.br',
            'orcid' => '0000-0002-1234-5678',
        ],
        [
            'givenName' => 'Coco Chanel',
            'familyName' => 'Fashion',
            'affiliation' => 'Chanel S.A.',
            'email' => 'coco.chanel@naoexiste.com.br',
            'orcid' => '0000-0002-1234-5678',
        ],
        [
            'givenName' => 'Giorgio Armani',
            'familyName' => 'Luxury',
            'affiliation' => 'Armani Group',
            'email' => 'yves.SL@naoexiste.com.br',
            'orcid' => '0000-0002-3456-7890',
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->authors = $this->createTestAuthors();
    }


    protected function getAffectedTables()
    {
        return array("authors", "author_settings");
    }

    private function createTestAuthors(): array
    {
        $authors = [];

        foreach ($this->testAuthorsData as $authorData) {
            $authors[] = $this->createAuthor($authorData);
        }

        return $authors;
    }

    private function createAuthor(array $authorData)
    {
        $authorDao = DAORegistry::getDAO('AuthorDAO');
        $authorId = [];

        $author = new Author();
        $author->setData('publicationId', 1234);
        $author->setGivenName($authorData['givenName'], $this->locale);
        $author->setFamilyName($authorData['familyName'], $this->locale);
        $author->setAffiliation($authorData['affiliation'], $this->locale);
        $author->setEmail($authorData['email'] ?? null);
        $author->setOrcid($authorData['orcid'] ?? null);

        return (int) $authorDao->insertObject($author);
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

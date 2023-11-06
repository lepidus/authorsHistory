<?php

use PKP\tests\DatabaseTestCase;
use PKP\services\PKPSchemaService; // SCHEMA_ constants
use APP\author\Author;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryDAO;

class AuthorsHistoryDAOTest extends DatabaseTestCase
{
    private $givenName = "Yves Saint Laurent";
    private $familyName = "Design";
    private $email = "yves.SL@naoexiste.com.br";
    private $affiliation = "Lepidus Tecnologia";
    private $locale = "pt_BR";
    private $authorId;

    public function setUp(): void
    {
        parent::setUp();
        $this->authorId = $this->createAuthor();
    }


    protected function getAffectedTables()
    {
        return array("authors", "author_settings");
    }

    private function createAuthor()
    {
        $authorDao = DAORegistry::getDAO('AuthorDAO');
        $authorId = [];

        $author = new Author();
        $author->setData('publicationId', 1234);
        $author->setGivenName($this->givenName, $this->locale);
        $author->setFamilyName($this->familyName, $this->locale);
        $author->setAffiliation($this->affiliation, $this->locale);
        $author->setEmail($this->email);
        $authorId = $authorDao->insertObject($author);

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

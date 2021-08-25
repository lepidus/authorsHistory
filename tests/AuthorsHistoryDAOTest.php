<?php
import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.services.PKPSchemaService'); // SCHEMA_ constants
import('classes.article.Author');
import('plugins.generic.authorsHistory.classes.AuthorsHistoryDAO');

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
        $authorsId = [];

        $author = new Author();
        $author->setGivenName($this->givenName, $this->locale);
		$author->setFamilyName($this->familyName, $this->locale);
		$author->setAffiliation($this->affiliation, $this->locale);
		$author->setEmail($this->email);
        $authorsId = $authorDao->insertObject($author);

        return $authorsId;
    }

    public function testAuthorIdRetrievingByGivenNameAndEmail()
    {
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $expectedValidationResult = [$this->authorId];
        $validationResult = $authorsHistoryDAO->getAuthorIdByGivenNameAndEmail($this->givenName, $this->email);
        $this->assertEquals($expectedValidationResult, $validationResult);
    }
}

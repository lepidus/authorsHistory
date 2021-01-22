<?php

/**
 * @file plugins/generic/AuthorsHistory/classes/AuthorsHistoryDAO.inc.php
 *
 * @class AuthorsHistoryDAO
 * @ingroup plugins_generic_authorsHistory
 *
 * Operations for retrieving authors data
 */

import('lib.pkp.classes.db.DAO');

class AuthorsHistoryDAO extends DAO {
    
    private function getAuthorsByORCID($orcid) {
        $resultAuthors = $this->retrieve(
            "SELECT author_id FROM author_settings WHERE setting_name = 'orcid' AND setting_value = '{$orcid}'"
        );
        $authors = (new DAOResultFactory($resultAuthors, $this, '_authorFromRow'))->toArray();

        return $authors;
    }

    private function getAuthorsByEmail($email) {
        $resultAuthors = $this->retrieve(
            "SELECT author_id FROM authors WHERE email = '{$email}'"
        );
        $authors = (new DAOResultFactory($resultAuthors, $this, '_authorFromRow'))->toArray();
        
        return $authors;
    }

    public function getAuthorSubmissions($orcid, $email) {
        $authors = $this->getAuthorsByEmail($email);
        if($orcid) {
            $authorsFromOrcid = $this->getAuthorsByORCID($orcid);
            $authors = array_unique(array_merge($authors, $authorsFromOrcid));
        }
        
        $submissions = array();
        foreach ($authors as $autorId) {
            $author = DAOregistry::getDAO('AuthorDAO')->getById($autorId);
            $authorPublication = DAORegistry::getDAO('PublicationDAO')->getById($author->getData('publicationId'));
            $authorSubmission = DAORegistry::getDAO('SubmissionDAO')->getById($authorPublication->getData('submissionId'));

            if($authorSubmission->getData('dateSubmitted') && !in_array($authorSubmission, $submissions)) {
                $submissions[] = $authorSubmission;
            }
        }
        
        return $submissions;
    }

    function _authorFromRow($row) {
        return $row['author_id'];
    }
}
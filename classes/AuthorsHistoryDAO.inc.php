<?php

/**
 * @file plugins/generic/authorsHistory/classes/AuthorsHistoryDAO.inc.php
 *
 * Copyright (c) 2020-2021 Lepidus Tecnologia
 * Copyright (c) 2020-2021 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 * 
 * @class AuthorsHistoryDAO
 * @ingroup plugins_generic_authorsHistory
 * @brief Operations for retrieving authors data
 */

import('lib.pkp.classes.db.DAO');

class AuthorsHistoryDAO extends DAO {
    
    private function getAuthorsByORCID($orcid) {
        $authorsResult = $this->retrieve(
            "SELECT author_id FROM author_settings WHERE setting_name = 'orcid' AND setting_value = ?",
            [$orcid]
        );
        $authors = (new DAOResultFactory($authorsResult, $this, '_authorFromRow'))->toArray();

        return $authors;
    }

    private function getAuthorsByEmail($email) {
        $authorsResult = $this->retrieve(
            "SELECT author_id FROM authors WHERE email = ?",
            [$email]
        );
        $authors = (new DAOResultFactory($authorsResult, $this, '_authorFromRow'))->toArray();
        
        return $authors;
    }

    public function getAuthorSubmissions($contextId, $orcid, $email) {
        $authors = $this->getAuthorsByEmail($email);
        if($orcid) {
            $authorsFromOrcid = $this->getAuthorsByORCID($orcid);
            $authors = array_unique(array_merge($authors, $authorsFromOrcid));
        }
        
        $submissions = array();
        foreach ($authors as $autorId) {
            $author = DAOregistry::getDAO('AuthorDAO')->getById($autorId);

            if(!is_null($author)){
                $authorPublication = DAORegistry::getDAO('PublicationDAO')->getById($author->getData('publicationId'));
                $authorSubmission = DAORegistry::getDAO('SubmissionDAO')->getById($authorPublication->getData('submissionId'));
    
                if($authorSubmission->getData('contextId') == $contextId && $authorSubmission->getData('dateSubmitted') && !in_array($authorSubmission, $submissions)) {
                    $submissions[] = $authorSubmission;
                }
            }
        }
        
        return $submissions;
    }

    function _authorFromRow($row) {
        return $row['author_id'];
    }
}
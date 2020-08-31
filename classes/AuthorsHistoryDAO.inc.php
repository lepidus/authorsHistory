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
    
    public function getAuthorPublicationsORCID($orcid) {
        $resultAutorzinhos = $this->retrieve(
            "SELECT author_id FROM author_settings WHERE setting_name = 'orcid' AND setting_value = '" . $orcid . "'"
        );
        $autorzinhos = (new DAOResultFactory($resultAutorzinhos, $this, '_authorFromRow'))->toArray();

        $publicacoes = array();
        foreach ($autorzinhos as $autorId) {
            $author = DAOregistry::getDAO('AuthorDAO')->getById($autorId);
            $submission = DAORegistry::getDAO('SubmissionDAO')->getById($author->getSubmissionId());

            $publicacoes[] = $submission->getCurrentPublication();
        }

        return $publicacoes;
    }

    function _authorFromRow($row) {
        return $row['author_id'];
    }
}
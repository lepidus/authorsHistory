<?php

/**
 * @file plugins/generic/authorsHistory/classes/AuthorsHistoryDAO.inc.php
 *
 * Copyright (c) 2020-2023 Lepidus Tecnologia
 * Copyright (c) 2020-2023 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @class AuthorsHistoryDAO
 *
 * @brief Operations for retrieving authors data
 */

namespace APP\plugins\generic\authorsHistory\classes;

use APP\facades\Repo;
use Illuminate\Support\Facades\DB;
use PKP\db\DAO;

class AuthorsHistoryDAO extends DAO
{
    public function getAuthorsByOrcid(string $orcid)
    {
        $result = DB::table('author_settings')
            ->select('author_id')
            ->where('setting_name', 'orcid')
            ->where('setting_value', $orcid)
            ->get();

        $authorsIds = [];
        foreach ($result as $row) {
            $authorsIds[] = get_object_vars($row)['author_id'];
        }

        return $authorsIds;
    }

    public function getSimilarAuthorsByEmail(string $email, int $contextId)
    {
        $result = DB::table('authors AS a')
            ->leftJoin('publications AS p', 'a.publication_id', '=', 'p.publication_id')
            ->leftJoin('submissions AS s', 'p.submission_id', '=', 's.submission_id')
            ->where('a.email', $email)
            ->where('s.context_id', $contextId)
            ->where('s.submission_progress', '=', '')
            ->select('a.author_id', 's.submission_id', 'p.publication_id')
            ->get();

        $similarAuthors = [];
        foreach ($result as $row) {
            $similarAuthors[] = get_object_vars($row);
        }

        return $similarAuthors;
    }


    public function getAuthorIdByGivenNameAndEmail($givenName, $email)
    {
        $result = DB::table('authors')
            ->leftJoin('author_settings', 'authors.author_id', '=', 'author_settings.author_id')
            ->where('author_settings.setting_name', 'givenName')
            ->where('author_settings.setting_value', $givenName)
            ->where('authors.email', $email)
            ->select('authors.author_id')
            ->get();

        $authorsIds = [];
        foreach ($result as $row) {
            $authorsIds[] = get_object_vars($row)['author_id'];
        }

        return $authorsIds;
    }

    public function getSimilarAuthors($contextId, $email, $orcid, $givenName, $itemsPerPageLimit)
    {
        $authors = [];

        if (!empty($email)) {
            $similarAuthorsByEmail = $this->getSimilarAuthorsByEmail($email, $contextId);
            $authors = (sizeof($similarAuthorsByEmail) > 10000000) ? $this->getAuthorIdByGivenNameAndEmail($givenName, $email) : $similarAuthorsByEmail;
        }

        // if (!empty($orcid)) {
        //     $authorsFromOrcid = $this->getAuthorsByORCID($orcid);
        //     $authors = array_unique(array_merge($authors, $authorsFromOrcid));
        // }

        return $authors;
    }

    public function getAuthorSubmissions($contextId, $orcid, $email, $givenName, $itemsPerPageLimit)
    {
        $similarAuthors = $this->getSimilarAuthors($contextId, $email, $orcid, $givenName, $itemsPerPageLimit);

        $submissions = [];
        foreach ($similarAuthors as $authorData) {
            $submissionId = $authorData['submission_id'];
            $author = Repo::author()->get($authorData['author_id']);
            
            if (!array_key_exists($submissionId, $submissions)) {
                $authorSubmission = Repo::submission()->get($submissionId);
                $submissions[$submissionId] = $authorSubmission;
            }
        }

        return $submissions;
    }
}

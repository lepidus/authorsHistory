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
    private function executeQuery($query)
    {
        $result = $query->get();

        $similarAuthors = [];
        foreach ($result as $row) {
            $similarAuthors[] = get_object_vars($row);
        }

        return $similarAuthors;
    }

    public function getSimilarAuthorsByOrcid(string $orcid)
    {
        $query = DB::table('author_settings AS ast')
            ->leftJoin('authors AS a', 'ast.author_id', '=', 'a.author_id')
            ->leftJoin('publications AS p', 'a.publication_id', '=', 'p.publication_id')
            ->leftJoin('submissions AS s', 'p.submission_id', '=', 's.submission_id')
            ->where('ast.setting_name', 'orcid')
            ->where('ast.setting_value', $orcid)
            ->where('s.context_id', $contextId)
            ->where('s.submission_progress', '=', '')
            ->select('a.author_id', 's.submission_id');

        return $this->executeQuery($query);
    }

    public function getSimilarAuthorsByEmailQuery(string $email, int $contextId)
    {
        $query = DB::table('authors AS a')
            ->leftJoin('publications AS p', 'a.publication_id', '=', 'p.publication_id')
            ->leftJoin('submissions AS s', 'p.submission_id', '=', 's.submission_id')
            ->where('a.email', $email)
            ->where('s.context_id', $contextId)
            ->where('s.submission_progress', '=', '')
            ->select('a.author_id', 's.submission_id');

        return $query;
    }

    public function getSimilarAuthorsByGivenNameAndEmail($givenName, $email)
    {
        $query = DB::table('authors AS a')
            ->leftJoin('author_settings AS ast', 'a.author_id', '=', 'ast.author_id')
            ->leftJoin('publications AS p', 'a.publication_id', '=', 'p.publication_id')
            ->leftJoin('submissions AS s', 'p.submission_id', '=', 's.submission_id')
            ->where('ast.setting_name', 'givenName')
            ->where('ast.setting_value', $givenName)
            ->where('a.email', $email)
            ->where('s.context_id', $contextId)
            ->where('s.submission_progress', '=', '')
            ->select('a.author_id', 's.submission_id');

        return $this->executeQuery($query);
    }

    public function getSimilarAuthors($contextId, $email, $orcid, $givenName, $itemsPerPageLimit)
    {
        $authors = [];

        if (!empty($email)) {
            $authorsByEmailQuery = $this->getSimilarAuthorsByEmailQuery($email, $contextId);
            $authors = ($authorsByEmailQuery->count() > $itemsPerPageLimit)
                ? $this->getSimilarAuthorsByGivenNameAndEmail($givenName, $email)
                : $this->executeQuery($authorsByEmailQuery);
        }

        if (!empty($orcid)) {
            $authorsFromOrcid = $this->getSimilarAuthorsByOrcid($orcid);
            $authors = array_unique(array_merge($authors, $authorsFromOrcid));
        }

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

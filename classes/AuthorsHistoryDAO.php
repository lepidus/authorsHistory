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
use APP\submission\Submission;
use APP\publication\Publication;

class AuthorsHistoryDAO extends DAO
{
    private function executeQuery($query)
    {
        $result = $query->get();

        $similarAuthors = [];
        foreach ($result as $row) {
            $rowData = get_object_vars($row);
            $similarAuthors[$rowData['author_id']] = $rowData;
        }

        return $similarAuthors;
    }

    public function getSimilarAuthorsByOrcid(string $orcid, int $contextId)
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

    public function getSimilarAuthorsByEmail(string $email, int $contextId, bool $executeQuery = false)
    {
        $query = DB::table('authors AS a')
            ->leftJoin('publications AS p', 'a.publication_id', '=', 'p.publication_id')
            ->leftJoin('submissions AS s', 'p.submission_id', '=', 's.submission_id')
            ->where('a.email', $email)
            ->where('s.context_id', $contextId)
            ->where('s.submission_progress', '=', '')
            ->select('a.author_id', 's.submission_id');

        if ($executeQuery) {
            return $this->executeQuery($query);
        }

        return $query;
    }

    public function getSimilarAuthorsByGivenNameAndEmail(string $givenName, string $email, int $contextId)
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
            $authorsByEmailQuery = $this->getSimilarAuthorsByEmail($email, $contextId);
            $authors = ($authorsByEmailQuery->count() > $itemsPerPageLimit)
                ? $this->getSimilarAuthorsByGivenNameAndEmail($givenName, $email, $contextId)
                : $this->executeQuery($authorsByEmailQuery);
        }

        if (!empty($orcid)) {
            $authorsFromOrcid = $this->getSimilarAuthorsByOrcid($orcid, $contextId);
            foreach ($authorsFromOrcid as $author) {
                if (!isset($authors[$author['author_id']])) {
                    $authors[$author['author_id']] = $author;
                }
            }
        }

        return $authors;
    }

    public function getAuthorSubmissions($contextId, $orcid, $email, $givenName, $itemsPerPageLimit)
    {
        $similarAuthors = $this->getSimilarAuthors($contextId, $email, $orcid, $givenName, $itemsPerPageLimit);

        $submissionsIds = [];
        foreach ($similarAuthors as $authorData) {
            $submissionId = $authorData['submission_id'];
            if (!array_key_exists($submissionId, $submissionsIds)) {
                $submissionsIds[$submissionId] = $submissionId;
            }
        }

        return $this->getSubmissionsFromIds(array_values($submissionsIds));
    }

    private function getSubmissionsFromIds(array $submissionsIds)
    {
        $result = DB::table('submissions AS s')
            ->leftJoin('publications AS p', 's.current_publication_id', '=', 'p.publication_id')
            ->leftJoin('publication_settings AS ps', 'p.publication_id', '=', 'ps.publication_id')
            ->whereIn('s.submission_id', $submissionsIds)
            ->whereIn('ps.setting_name', ['urlPath', 'title', 'subtitle'])
            ->select('s.submission_id', 's.status', 'p.publication_id', 'ps.locale', 'ps.setting_name', 'ps.setting_value')
            ->get();

        $submissions = [];
        foreach ($result as $row) {
            $rowData = get_object_vars($row);
            $submissionId = $rowData['submission_id'];

            if (!isset($submissions[$submissionId])) {
                $publication = new Publication();
                $publication->setData('id', $rowData['publication_id']);

                $submission = new Submission();
                $submission->setAllData([
                    'id' => $submissionId,
                    'status' => $rowData['status'],
                    'currentPublicationId' => $rowData['publication_id'],
                    'publications' => [$publication]
                ]);

                $submissions[$submissionId] = $submission;
            }

            $publication = $submissions[$submissionId]->getCurrentPublication();
            if ($rowData['setting_name'] == 'urlPath') {
                $publication->setData($rowData['setting_name'], $rowData['setting_value']);
                continue;
            }

            $publication->setData($rowData['setting_name'], $rowData['setting_value'], $rowData['locale']);
        }

        return $submissions;
    }
}

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

    public function getAuthorsByEmail(string $email)
    {
        $result = DB::table('authors')
            ->select('author_id')
            ->where('email', $email)
            ->get();

        $authorsIds = [];
        foreach ($result as $row) {
            $authorsIds[] = get_object_vars($row)['author_id'];
        }

        return $authorsIds;
    }


    public function getAuthorIdByGivenNameAndEmail($givenName, $email)
    {
        $result = DB::table('authors')
            ->join('author_settings', 'authors.author_id', '=', 'author_settings.author_id')
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

    public function getSimilarAuthors($email, $orcid, $givenName, $itemsPerPageLimit)
    {
        $authors = [];

        if (!empty($email)) {
            $authorsByEmail = $this->getAuthorsByEmail($email);
            $authors = (sizeof($authorsByEmail) > $itemsPerPageLimit) ? $this->getAuthorIdByGivenNameAndEmail($givenName, $email) : $authorsByEmail;
        }

        if (!empty($orcid)) {
            $authorsFromOrcid = $this->getAuthorsByORCID($orcid);
            $authors = array_unique(array_merge($authors, $authorsFromOrcid));
        }

        return $authors;
    }

    public function getAuthorSubmissions($contextId, $orcid, $email, $givenName, $itemsPerPageLimit)
    {
        $authors = $this->getSimilarAuthors($email, $orcid, $givenName, $itemsPerPageLimit);

        $submissions = [];
        foreach ($authors as $authorId) {
            $author = Repo::author()->get($authorId);

            if (!is_null($author)) {
                $authorPublication = Repo::publication()->get($author->getData('publicationId'));
                $authorSubmission = Repo::submission()->get($authorPublication->getData('submissionId'));

                if (
                    $authorSubmission->getData('contextId') == $contextId
                    && $authorSubmission->getData('dateSubmitted')
                    && !array_key_exists($authorSubmission->getId(), $submissions)
                ) {
                    $submissions[$authorSubmission->getId()] = $authorSubmission;
                }
            }
        }

        return $submissions;
    }
}

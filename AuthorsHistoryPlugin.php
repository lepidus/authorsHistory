<?php
/**
 * @file plugins/generic/AuthorsHistory/AuthorsHistoryPlugin.inc.php
 *
 * Copyright (c) 2020-2023 Lepidus Tecnologia
 * Copyright (c) 2020-2023 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @class AuthorsHistoryPlugin
 * @ingroup plugins_generic_authorsHistory
 * @brief Plugin class for the Authors History plugin.
 */

namespace APP\plugins\generic\authorsHistory;

use PKP\plugins\GenericPlugin;
use APP\core\Application;
use PKP\db\DAORegistry;
use PKP\plugins\Hook;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryDAO;

class AuthorsHistoryPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if (Application::isUnderMaintenance()) {
            return $success;
        }

        if ($success && $this->getEnabled($mainContextId)) {
            $authorsHistoryDAO = new AuthorsHistoryDAO();
            DAORegistry::registerDAO('AuthorsHistoryDAO', $authorsHistoryDAO);

            Hook::add('Template::Workflow::Publication', array($this, 'addToWorkflow'));
        }

        return $success;
    }

    private function getAuthorsData($submission, $itemsPerPageLimit)
    {
        $listAuthorsData = array();
        $publication = $submission->getCurrentPublication();
        $correspondenceContact = $publication->getData('primaryContactId');
        $contextId = $submission->getData('contextId');

        foreach ($publication->getData('authors') as $author) {
            $authorData = array();
            $authorData['name'] = $author->getFullName();
            $authorData['orcid'] = $author->getOrcid();
            $authorData['email'] = $author->getEmail();
            $authorData['correspondingAuthor'] = ($correspondenceContact == $author->getId());

            $givenName = $author->getLocalizedGivenName();
            $authorsHistoryDAO = new AuthorsHistoryDAO();

            $authorData['submissions'] = $authorsHistoryDAO->getAuthorSubmissions(
                $contextId,
                $authorData['orcid'],
                $authorData['email'],
                $givenName,
                $itemsPerPageLimit
            );

            $listAuthorsData[] = $authorData;
        }
        return $listAuthorsData;
    }

    public function addToWorkflow($hookName, $params)
    {
        $smarty = &$params[1];
        $output = &$params[2];
        $submission = $smarty->getTemplateVars('submission');
        $request = Application::get()->getRequest();
        $user = $request->getUser();

        $smarty->assign(
            'userIsManager',
            $user->hasRole(Application::getWorkflowTypeRoles()[WORKFLOW_TYPE_EDITORIAL], $request->getContext()->getId())
        );
        $itemsPerPage = $request->getContext()->getData('itemsPerPage');
        $smarty->assign([
            'listDataAuthors' => $this->getAuthorsData($submission, $itemsPerPage),
            'itemsPerPage', $itemsPerPage,
            'submissionType' => $this->getSubmissionType()
        ]);

        $output .= sprintf(
            '<tab id="authorsHistory" label="%s">%s</tab>',
            __('plugins.generic.authorsHistory.displayName'),
            $smarty->fetch($this->getTemplateResource('authorsHistory.tpl'))
        );
    }

    private function getSubmissionType(): string
    {
        $applicationName = substr(Application::getName(), 0, 3);

        if($applicationName == 'ops') {
            return 'preprint';
        }

        return 'article';
    }

    public function getDisplayName()
    {
        return __('plugins.generic.authorsHistory.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.authorsHistory.description');
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('APP\plugins\generic\authorsHistory\AuthorsHistoryPlugin', '\AuthorsHistoryPlugin');
}

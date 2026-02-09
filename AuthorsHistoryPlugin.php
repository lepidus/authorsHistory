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

use APP\core\Application;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryDAO;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryHandler;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\security\Role;
use PKP\stageAssignment\StageAssignment;

class AuthorsHistoryPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if (Application::isUnderMaintenance()) {
            return $success;
        }

        if ($success) {
            $authorsHistoryDAO = new AuthorsHistoryDAO();
            DAORegistry::registerDAO('AuthorsHistoryDAO', $authorsHistoryDAO);

            Hook::add('TemplateManager::display', [$this, 'callbackTemplateDisplay']);
            Hook::add('LoadHandler', [$this, 'callbackHandleContent']);
        }

        return $success;
    }

    public function callbackTemplateDisplay(string $hookName, array $args): bool
    {
        /** @var TemplateManager $templateMgr */
        $templateMgr = $args[0];

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return false;
        }
        if (!$this->getEnabled($context->getId())) {
            return false;
        }

        $endpoint = $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            $context->getPath(),
            'authorsHistory',
            'index'
        );
        $pluginPath = $request->getBaseUrl() . '/' . $this->getPluginPath();
        $config = [
            'endpoint' => $endpoint,
            'tabLabel' => __('plugins.generic.authorsHistory.displayName'),
            'loadingLabel' => __('common.loading'),
            'loadErrorLabel' => __('plugins.generic.authorsHistory.loadError'),
            'submissionIdErrorLabel' => __('plugins.generic.authorsHistory.submissionIdError'),
        ];

        $templateMgr->addStyleSheet(
            'authorsHistoryStyles',
            $pluginPath . '/styles/authorsHistory.css',
            [
                'contexts' => 'backend',
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
            ]
        );
        $templateMgr->addJavaScript(
            'authorsHistoryConfig',
            'window.pkpAuthorsHistoryConfig = ' . json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';',
            [
                'contexts' => 'backend',
                'inline' => true,
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
            ]
        );
        $templateMgr->addJavaScript(
            'authorsHistoryPagination',
            $pluginPath . '/templates/pagination.js',
            [
                'contexts' => 'backend',
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
            ]
        );
        $templateMgr->addJavaScript(
            'authorsHistoryDashboard',
            $pluginPath . '/js/authorsHistoryDashboard.js',
            [
                'contexts' => 'backend',
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
            ]
        );

        return false;
    }

    public function callbackHandleContent(string $hookName, array $args): bool
    {
        $page = &$args[0];
        $op = &$args[1];

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context || !$this->getEnabled($context->getId())) {
            return false;
        }

        if ($page !== 'authorsHistory' || $op !== 'index') {
            return false;
        }

        $args[3] = new AuthorsHistoryHandler($this);
        return true;
    }

    public function getAuthorsData(Submission $submission, int $itemsPerPageLimit): array
    {
        $listAuthorsData = [];
        $publication = $submission->getCurrentPublication();
        $correspondenceContact = $publication->getData('primaryContactId');
        $contextId = $submission->getData('contextId');
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $authors = $publication->getData('authors') ?? [];

        foreach ($authors as $author) {
            $authorData = [
                'name' => $author->getFullName(),
                'orcid' => $author->getOrcid(),
                'email' => $author->getEmail(),
                'correspondingAuthor' => ($correspondenceContact == $author->getId()),
            ];

            $givenName = $author->getLocalizedGivenName();
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

    public function userCanAccessEditorialHistory($user, Submission $submission, int $contextId): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole([Role::ROLE_ID_SITE_ADMIN], PKPApplication::SITE_CONTEXT_ID)) {
            return true;
        }

        if ($user->hasRole([Role::ROLE_ID_MANAGER], $contextId)) {
            return true;
        }

        if (!$user->hasRole([Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT], $contextId)) {
            return false;
        }

        return StageAssignment::withSubmissionIds([$submission->getId()])
            ->withUserId($user->getId())
            ->withRoleIds([Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT])
            ->exists();
    }

    public function getSubmissionType(): string
    {
        $applicationName = substr(Application::getName(), 0, 3);

        if ($applicationName == 'ops') {
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

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
use APP\facades\Repo;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryDAO;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryHandler;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response;
use PKP\core\PKPApplication;
use PKP\core\PKPBaseController;
use PKP\db\DAORegistry;
use PKP\handler\APIHandler;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\security\Role;
use PKP\submission\PKPSubmission;
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
            $this->addApiEndpoint();
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
        $apiEndpoint = rtrim($request->getBaseUrl(), '/') . '/index.php/' . $context->getPath() . '/api/v1/submissions/authorsHistory';
        $config = [
            'endpoint' => $endpoint,
            'apiEndpoint' => $apiEndpoint,
            'tabLabel' => __('plugins.generic.authorsHistory.displayName'),
            'loadingLabel' => __('common.loading'),
            'loadErrorLabel' => __('plugins.generic.authorsHistory.loadError'),
            'submissionIdErrorLabel' => __('plugins.generic.authorsHistory.submissionIdError'),
            'noPublicationsLabel' => __('plugins.generic.authorsHistory.noPublications'),
            'noOrcidLabel' => __('plugins.generic.authorsHistory.noORCID'),
            'orcidLabel' => __('plugins.generic.authorsHistory.orcid'),
            'emailLabel' => __('email.email'),
            'pagesLabel' => __('plugins.generic.authorsHistory.pages'),
            'correspondingAuthorLabel' => __('submission.submit.selectPrincipalContact'),
        ];

        $templateMgr->addJavaScript(
            'authorsHistoryConfig',
            'window.pkpAuthorsHistoryConfig = ' . json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';',
            [
                'contexts' => 'backend',
                'inline' => true,
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
            ]
        );
        $pluginDir = __DIR__;
        $buildJsPath = $pluginDir . '/public/build/build.iife.js';
        $buildCssPath = $pluginDir . '/public/build/build.css';

        if (file_exists($buildJsPath) && file_exists($buildCssPath)) {
            $templateMgr->addStyleSheet(
                'authorsHistoryStylesBuild',
                $pluginPath . '/public/build/build.css',
                [
                    'contexts' => 'backend',
                    'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
                ]
            );
            $templateMgr->addJavaScript(
                'authorsHistoryDashboardBuild',
                $pluginPath . '/public/build/build.iife.js',
                [
                    'contexts' => 'backend',
                    'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
                    'inline' => false,
                ]
            );
        } else {
            $templateMgr->addStyleSheet(
                'authorsHistoryStylesLegacy',
                $pluginPath . '/styles/authorsHistory.css',
                [
                    'contexts' => 'backend',
                    'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
                ]
            );
            $templateMgr->addJavaScript(
                'authorsHistoryDashboardLegacy',
                $pluginPath . '/js/authorsHistoryDashboard.js',
                [
                    'contexts' => 'backend',
                    'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
                ]
            );
        }

        return false;
    }

    private function addApiEndpoint(): void
    {
        Hook::add('APIHandler::endpoints::submissions', function (string $hookName, PKPBaseController &$apiController, APIHandler $apiHandler): bool {
            $apiHandler->addRoute(
                'GET',
                'authorsHistory',
                function (IlluminateRequest $request): JsonResponse {
                    $pkpRequest = Application::get()->getRequest();
                    $context = $pkpRequest->getContext();
                    $user = $pkpRequest->getUser();
                    if (!$context || !$this->getEnabled((int) $context->getId())) {
                        return response()->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
                    }

                    $submissionId = (int) $request->query('submissionId');
                    if ($submissionId < 1) {
                        return response()->json(['error' => 'submissionId is required'], Response::HTTP_BAD_REQUEST);
                    }

                    $submission = Repo::submission()->get($submissionId);
                    if (!$submission || (int) $submission->getData('contextId') !== (int) $context->getId()) {
                        return response()->json(['error' => 'Submission not found'], Response::HTTP_NOT_FOUND);
                    }

                    if (!$this->userCanAccessEditorialHistory($user, $submission, (int) $context->getId())) {
                        return response()->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
                    }

                    $itemsPerPage = (int) $context->getData('itemsPerPage');
                    if ($itemsPerPage < 1) {
                        $itemsPerPage = 25;
                    }

                    return response()->json($this->getAuthorsDataForApi($submission, $itemsPerPage), Response::HTTP_OK);
                },
                'authorsHistory.get',
                [
                    Role::ROLE_ID_SITE_ADMIN,
                    Role::ROLE_ID_MANAGER,
                    Role::ROLE_ID_SUB_EDITOR,
                    Role::ROLE_ID_ASSISTANT,
                    Role::ROLE_ID_AUTHOR,
                    Role::ROLE_ID_REVIEWER,
                ]
            );

            return false;
        });
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
        if (!$publication) {
            return $listAuthorsData;
        }
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

    public function getAuthorsDataForApi(Submission $submission, int $itemsPerPageLimit): array
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $contextPath = $context ? $context->getPath() : null;
        $submissionType = $this->getSubmissionType();
        $authorsData = $this->getAuthorsData($submission, $itemsPerPageLimit);

        foreach ($authorsData as &$authorData) {
            $formattedSubmissions = [];
            foreach ($authorData['submissions'] as $submissionItem) {
                $submissionPublication = $submissionItem->getCurrentPublication();
                $status = (int) $submissionItem->getData('status');
                $formattedSubmissions[] = [
                    'id' => (int) $submissionItem->getId(),
                    'title' => $submissionPublication ? $submissionPublication->getLocalizedFullTitle() : '',
                    'status' => $status,
                    'statusLabel' => __($submissionItem->getStatusKey()),
                    'urlWorkflow' => $request->getDispatcher()->url(
                        $request,
                        Application::ROUTE_PAGE,
                        $contextPath,
                        'workflow',
                        'access',
                        [$submissionItem->getId()]
                    ),
                    'urlPublished' => $status === PKPSubmission::STATUS_PUBLISHED
                        ? $request->getDispatcher()->url(
                            $request,
                            Application::ROUTE_PAGE,
                            $contextPath,
                            $submissionType,
                            'view',
                            [$submissionItem->getBestId()]
                        )
                        : null,
                ];
            }
            $authorData['submissions'] = $formattedSubmissions;
        }
        unset($authorData);

        return $authorsData;
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

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
use APP\template\TemplateManager;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use PKP\core\PKPBaseController;
use PKP\handler\APIHandler;
use PKP\security\Role;
use PKP\submission\PKPSubmission;

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

            $request = Application::get()->getRequest();
            $templateMgr = TemplateManager::getManager($request);
            $templateMgr->addJavaScript(
                'AuthorsHistory',
                "{$request->getBaseUrl()}/{$this->getPluginPath()}/public/build/build.iife.js",
                [
                    'inline' => false,
                    'contexts' => ['backend'],
                    'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
                ]
            );
            $templateMgr->addStyleSheet(
                'AuthorsHistoryStyle',
                "{$request->getBaseUrl()}/{$this->getPluginPath()}/public/build/build.css",
                ['contexts' => ['backend']]
            );

            $this->registerAuthorsHistoryRoute();
        }

        return $success;
    }

    private function registerAuthorsHistoryRoute(): void
    {
        Hook::add('APIHandler::endpoints::submissions', function (string $hookName, PKPBaseController &$apiController, APIHandler $apiHandler): bool {
            $apiHandler->addRoute(
                'GET',
                'authorsHistory',
                function (IlluminateRequest $request): JsonResponse {
                    $pkpRequest = Application::get()->getRequest();
                    $context = $pkpRequest->getContext();

                    if (!$context || !$this->getEnabled((int) $context->getId())) {
                        return response()->json(
                            ['error' => __('plugins.generic.authorsHistory.error.submissionNotFound')],
                            Response::HTTP_NOT_FOUND
                        );
                    }

                    $submissionId = (int) $request->query('submissionId');

                    if ($submissionId < 1) {
                        return response()->json(
                            ['error' => __('plugins.generic.authorsHistory.error.submissionIdRequired')],
                            Response::HTTP_BAD_REQUEST
                        );
                    }

                    $submission = \APP\facades\Repo::submission()->get($submissionId);

                    if (!$submission || (int) $submission->getData('contextId') !== (int) $context->getId()) {
                        return response()->json(
                            ['error' => __('plugins.generic.authorsHistory.error.submissionNotFound')],
                            Response::HTTP_NOT_FOUND
                        );
                    }

                    $publication = $submission->getCurrentPublication();
                    $correspondenceContact = $publication->getData('primaryContactId');
                    $contextId = (int) $context->getId();
                    $itemsPerPage = (int) $context->getData('itemsPerPage');
                    if ($itemsPerPage < 1) {
                        $itemsPerPage = 25;
                    }

                    $authorsHistoryDAO = new AuthorsHistoryDAO();
                    $submissionType = $this->getSubmissionType();
                    $listAuthorsData = [];

                    foreach ($publication->getData('authors') as $author) {
                        $authorData = [
                            'name' => $author->getFullName(),
                            'orcid' => $author->getOrcid(),
                            'email' => $author->getEmail(),
                            'correspondingAuthor' => ($correspondenceContact == $author->getId()),
                        ];

                        $givenName = $author->getLocalizedGivenName();
                        $authorSubmissions = $authorsHistoryDAO->getAuthorSubmissions(
                            $contextId,
                            $authorData['orcid'],
                            $authorData['email'],
                            $givenName,
                            $itemsPerPage
                        );

                        $authorData['submissions'] = [];
                        foreach ($authorSubmissions as $authorSubmission) {
                            $currentPublication = $authorSubmission->getCurrentPublication();
                            $authorData['submissions'][] = [
                                'id' => $authorSubmission->getId(),
                                'title' => $currentPublication ? $currentPublication->getLocalizedFullTitle() : '',
                                'status' => $authorSubmission->getData('status'),
                                'statusLabel' => __($authorSubmission->getStatusKey()),
                                'urlWorkflow' => $pkpRequest->getDispatcher()->url(
                                    $pkpRequest,
                                    Application::ROUTE_PAGE,
                                    null,
                                    'workflow',
                                    'access',
                                    [$authorSubmission->getId()]
                                ),
                                'urlPublished' => ($authorSubmission->getData('status') == PKPSubmission::STATUS_PUBLISHED)
                                    ? $pkpRequest->getDispatcher()->url(
                                        $pkpRequest,
                                        Application::ROUTE_PAGE,
                                        null,
                                        $submissionType,
                                        'view',
                                        [$authorSubmission->getBestId()]
                                    )
                                    : null,
                            ];
                        }

                        $listAuthorsData[] = $authorData;
                    }

                    return response()->json($listAuthorsData, Response::HTTP_OK);
                },
                'authorsHistory.get',
                [
                    Role::ROLE_ID_SITE_ADMIN,
                    Role::ROLE_ID_MANAGER,
                    Role::ROLE_ID_SUB_EDITOR,
                ]
            );

            return false;
        });
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

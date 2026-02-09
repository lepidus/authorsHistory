<?php

/**
 * @file plugins/generic/authorsHistory/classes/AuthorsHistoryHandler.php
 *
 * Copyright (c) 2020-2023 Lepidus Tecnologia
 * Copyright (c) 2020-2023 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @class AuthorsHistoryHandler
 * @ingroup plugins_generic_authorsHistory
 * @brief Endpoint handler for rendering the authors history tab content in OJS/OPS 3.5.
 */

namespace APP\plugins\generic\authorsHistory\classes;

use APP\facades\Repo;
use APP\handler\Handler;
use APP\plugins\generic\authorsHistory\AuthorsHistoryPlugin;
use APP\template\TemplateManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthorsHistoryHandler extends Handler
{
    private AuthorsHistoryPlugin $plugin;

    public function __construct(AuthorsHistoryPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Render the tab content for a submission.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function index($args, $request): void
    {
        $context = $request->getContext();
        $user = $request->getUser();
        $submissionId = (int) $request->getUserVar('submissionId');

        if (!$context || !$submissionId) {
            throw new NotFoundHttpException();
        }

        $submission = Repo::submission()->get($submissionId);
        if (!$submission || (int) $submission->getData('contextId') !== (int) $context->getId()) {
            throw new NotFoundHttpException();
        }

        if (!$this->plugin->userCanAccessEditorialHistory($user, $submission, (int) $context->getId())) {
            throw new NotFoundHttpException();
        }

        $itemsPerPage = (int) $context->getData('itemsPerPage');
        if ($itemsPerPage < 1) {
            $itemsPerPage = 25;
        }

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'listDataAuthors' => $this->plugin->getAuthorsData($submission, $itemsPerPage),
            'itemsPerPage' => $itemsPerPage,
            'submissionType' => $this->plugin->getSubmissionType(),
            'userCanAccessWorkflow' => true,
        ]);
        $templateMgr->display($this->plugin->getTemplateResource('authorsHistory.tpl'));
    }
}

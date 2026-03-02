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
use PKP\core\PKPRequest;
use PKP\db\DAORegistry;
use PKP\plugins\Hook;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryDAO;
use APP\plugins\generic\authorsHistory\classes\api\v1\AuthorsHistoryController;
use APP\template\TemplateManager;
use PKP\core\APIRouter;
use PKP\handler\APIHandler;

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

            Hook::add('Dispatcher::dispatch', function (string $hookName, array $params): bool {
                $request = $params[0];
                $router = $request->getRouter();

                if (!($router instanceof APIRouter)) {
                    return Hook::CONTINUE;
                }

                if (!str_contains($request->getRequestPath(), 'api/v1/authorsHistory')) {
                    return Hook::CONTINUE;
                }

                $handler = new APIHandler(new AuthorsHistoryController());
                $router->setHandler($handler);
                $handler->runRoutes();
                exit;
            });
        }

        return $success;
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

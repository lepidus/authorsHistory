<?php
/**
 * @file plugins/generic/AuthorsHistory/AuthorsHistoryPlugin.inc.php
 *
 * @class AuthorsHistoryPlugin
 * @ingroup plugins_generic_authorsHistory
 *
 * @brief Plugin class for the Authors History plugin.
 */
import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.AuthorsHistory.classes.AuthorsHistoryDAO');


class AuthorsHistoryPlugin extends GenericPlugin {
    public function register($category, $path, $mainContextId = NULL) {
		$success = parent::register($category, $path, $mainContextId);
        
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE'))
            return true;
        
        if ($success && $this->getEnabled($mainContextId)) {
            $authorsHistoryDAO = new AuthorsHistoryDAO();
			DAORegistry::registerDAO('AuthorsHistoryDAO', $authorsHistoryDAO);

            HookRegistry::register('Template::Workflow::Publication', array($this, 'addToWorkflow'));
        }
        
        return $success;
    }

    private function obterDadosAutores($submission){
        $listaDadosAutores = array();
        $contatoCorrespondencia = $submission->getCurrentPublication()->getData('primaryContactId');

        foreach ($submission->getAuthors() as $author) {
            $authorData = array();
            $authorData['nome'] = $author->getFullName();
            $authorData['orcid'] = $author->getOrcid();
            $authorData['email'] = $author->getEmail();
            $authorData['autorCorrespondente'] = ($contatoCorrespondencia == $author->getId());

            $authorsHistoryDAO = new AuthorsHistoryDAO();
            $authorData['submissions'] = $authorsHistoryDAO->getAuthorSubmissions($authorData['orcid'], $authorData['email']);

            $listaDadosAutores[] = $authorData;
        }
        return $listaDadosAutores;
    }

    function addToWorkflow($hookName, $params) {
        $smarty =& $params[1];
		$output =& $params[2];
        $submission = $smarty->get_template_vars('submission');
        $request = Application::get()->getRequest();
        $user = $request->getUser();

        $userService = Services::get('user');
        $smarty->assign(
            'userIsManager',
            $user->hasRole(Application::getWorkflowTypeRoles()[WORKFLOW_TYPE_EDITORIAL], $request->getContext()->getId())
        );
        $smarty->assign('listaDadosAutores', $this->obterDadosAutores($submission));
        
        $output .= sprintf(
			'<tab id="authorsHistory" label="%s">%s</tab>',
			__('plugins.generic.authorsHistory.displayName'),
			$smarty->fetch($this->getTemplateResource('authorsHistory.tpl'))
		);
    }

    public function getDisplayName() {
		return __('plugins.generic.authorsHistory.displayName');
	}

	public function getDescription() {
		return __('plugins.generic.authorsHistory.description');
	}
}
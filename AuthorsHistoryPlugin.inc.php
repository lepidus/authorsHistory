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

    public function addToWorkflow($hookName, $params) {
        $smarty =& $params[1];
		$output =& $params[2];
        $submission = $smarty->get_template_vars('submission');
        $listaDadosAutores = array();

        foreach ($submission->getAuthors() as $author) {
            $authorData = array();
            $authorData['nome'] = $author->getFullName();
            $authorData['orcid'] = $author->getOrcid();
            $authorData['email'] = $author->getEmail();

            $authorsHistoryDAO = new AuthorsHistoryDAO();
            $authorData['submissions'] = $authorsHistoryDAO->getAuthorSubmissions($authorData['orcid'], $authorData['email']);

            $listaDadosAutores[] = $authorData;
        }

        $smarty->assign('listaDadosAutores', $listaDadosAutores);
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
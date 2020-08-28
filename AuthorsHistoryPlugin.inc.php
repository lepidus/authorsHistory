<?php
/**
 * @file plugins/generic/documentMetadataChecklist/AuthorDOIScreeningPlugin.inc.php
 *
 * @class DocumentMetadataChecklistPlugin
 * @ingroup plugins_generic_documentMetadataChecklist
 *
 * @brief Plugin class for the Document Metadata Checklist plugin.
 */
import('lib.pkp.classes.plugins.GenericPlugin');

class DocumentMetadataChecklistPlugin extends GenericPlugin {
    public function register($category, $path, $mainContextId = NULL) {
		$success = parent::register($category, $path, $mainContextId);
        
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE'))
            return true;
        
        if ($success && $this->getEnabled($mainContextId)) {

        }
        
        return $success;
    }

    public function getDisplayName() {
		return __('plugins.generic.documentMetadataChecklist.displayName');
	}

	public function getDescription() {
		return __('plugins.generic.documentMetadataChecklist.description');
	}
}
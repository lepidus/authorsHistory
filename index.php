<?php
/**
 * @defgroup plugins_generic_authorsHistory
 */

/**
 * @file plugins/generic/authorsHistory/index.php
 *
 * Copyright (c) 2020-2023 Lepidus Tecnologia
 * Copyright (c) 2020-2023 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @ingroup plugins_generic_authorsHistory
 * @brief Wrapper for the Authors History Plugin.
 *
 */
require_once('AuthorsHistoryPlugin.inc.php');
return new AuthorsHistoryPlugin();

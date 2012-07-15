<?php
/**
 * Poldek Plugin:  query poldek for package info
 *
 * Add poldek tags to dokuwiki
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Elan Ruusamäe <glen@delfi.ee>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if(!defined('DOKU_DATA')) define('DOKU_DATA',DOKU_INC.'data/');

require_once(DOKU_PLUGIN.'action.php');
require_once(DOKU_INC.'inc/search.php');
require_once(DOKU_INC.'inc/io.php');

/**
 * Poldek Action Plugin: Update poldek indexes
 *
 * @author     Elan Ruusamäe <glen@delfi.ee>
 */
class action_plugin_poldek extends DokuWiki_Action_Plugin {

    /**
     * if true our process is already running
     */
    private $run = false;

    /**
     * Register its handlers with the dokuwiki's event controller
     */
    function register(&$controller) {
        $controller->register_hook('INDEXER_TASKS_RUN', 'BEFORE', $this, 'cron', array());
    }

    /**
     * Update poldek indexes in the background
     */
    function cron(&$event, $param) {
		if ($this->run) {
			return;
		}

        $this->run = true;

        global $ID;
		$packages = p_get_metadata($ID, "plugin_" . $this->getPluginName());

		if (!empty($packages)) {
			$helper = $this->loadHelper($this->getPluginName(), true);
			// TODO: skip updating if last update is fresh enough
			$helper->sync();
		}
    }
}

//Setup VIM: ex: noet ts=4 enc=utf-8 :

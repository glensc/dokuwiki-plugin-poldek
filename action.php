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
		$controller->register_hook('PARSER_CACHE_USE','BEFORE', $this, 'cache');
	}

	/**
	 * Update poldek indexes in the background
	 */
	function cron(&$event, $param) {
		if ($this->run) {
			return;
		}

		$this->run = true;

		$helper = $this->loadHelper($this->getPluginName(), true);
		$helper->sync(true);
	}

	/**
	 * Update page cache dependencies
	 */
	function cache(&$event, $param) {
		global $conf, $ID;

		$cache =& $event->data;

		// we're only interested in wiki pages and supported render modes
		if (!isset($cache->page)) return;
		if (!isset($cache->mode) || $cache->mode != 'xhtml') return;

		$packages = p_get_metadata($ID, "plugin_" . $this->getPluginName());
		if (empty($packages)) {
			return;
		}

		$helper = $this->loadHelper($this->getPluginName(), true);
		$pkg_cache = $helper->getCache();
		if (file_exists($pkg_cache)) {
			$cache->depends['files'][] = $pkg_cache;
		}
	}
}

//Setup VIM: ex: noet ts=4 enc=utf-8 :

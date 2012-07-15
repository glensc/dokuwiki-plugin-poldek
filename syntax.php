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
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_poldek extends DokuWiki_Syntax_Plugin {
	/**
	 * What kind of syntax are we?
	 */
	public function getType() {
		return 'substition';
	}

	/**
	 * Where to sort in?
	 */
	public function getSort() {
		return 306;
	}

	/**
	 * Connect pattern to lexer
	 */
	public function connectTo($mode) {
		$this->Lexer->addSpecialPattern('\{\{poldek>.+?\}\}', $mode, 'plugin_poldek');
	}


	/**
	 * Handle the match
	 */
	public function handle($match, $state, $pos, &$handler) {
		$raw = substr($match, 9, -2);

		$data = array('pkg' => $raw);
		return $data;
	}

	/**
	 * Create output
	 */
	public function render($format, &$renderer, $data) {
		global $ID;
		global $REV;

		if ($format == "metadata") {
			// add each package to metadata for group retrieval
			$packages = &$renderer->meta["plugin_" . $this->getPluginName()];
			$packages[] = $data['pkg'];
			$packages = array_unique($packages);
			return true;
		} elseif ($format != 'xhtml') {
			return false;
		}

		$helper = $this->loadHelper($this->getPluginName(), true);

		// TODO: in preview and in history pages ($REV) the metadata is
		// outdated (it is always for recent page version)
		$packages = p_get_metadata($ID, "plugin_" . $this->getPluginName());
		$renderer->doc .= $helper->ls($packages, $data['pkg']);

		return true;
	}

	/**
	 * FIXME: Somewhy Syntax plugins don't have this method. duplicate
	 *
	 * Loads a given helper plugin (if enabled)
	 *
	 * @author  Esther Brunner <wikidesign@gmail.com>
	 *
	 * @param   $name   name of plugin to load
	 * @param   $msg    message to display in case the plugin is not available
	 *
	 * @return  object  helper plugin object
	 */
	function loadHelper($name, $msg){
		if (!plugin_isdisabled($name)){
			$obj =& plugin_load('helper',$name);
		}else{
			$obj = null;
		}
		if (is_null($obj) && $msg) msg("Helper plugin $name is not available or invalid.",-1);
		return $obj;
	}
}

//Setup VIM: ex: noet ts=4 enc=utf-8 :

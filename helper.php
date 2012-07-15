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
class helper_plugin_poldek extends DokuWiki_Plugin {

	/**
	 * Update poldek indexes for active repos
	 */
	public function sync() {
		$this->exec("--up");
	}

	/**
	 * Query poldek database
	 */
	public function query($cmd, &$lines) {
		return $this->exec('-Q --shcmd='.escapeshellarg($cmd), $lines);
	}

	private function exec($cmd, &$lines = null) {
		global $conf;
		$cachedir = $conf['cachedir'].'/'.$this->getPluginName();

		// base poldek command
		$poldek = 'exec poldek -q --skip-installed -O cachedir=' . escapeshellarg($cachedir);

		$repos = $this->getConf('repos');
		foreach (explode(',', $repos) as $repo) {
			$poldek .= ' --sn '.escapeshellarg(trim($repo));
		}

		// proxies setup
		$http_proxy = $this->getConf('http_proxy');
		if ($http_proxy) {
			$poldek .= ' -O '.escapeshellarg("proxy=http: {$http_proxy}");
		}
		$ftp_proxy = $this->getConf('ftp_proxy');
		if ($ftp_proxy) {
			$poldek.= ' -O '.escapeshellarg("proxy=ftp: {$ftp_proxy}");
		}

		$poldek .= " $cmd";

		error_Log("poldek [$poldek]");
		exec($poldek, $lines, $rc);
		return $rc;
	}
}

//Setup VIM: ex: noet ts=4 enc=utf-8 :

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

	public function ls($packages, $package) {
		static $cache;
		$key = md5(serialize($packages));
		if (isset($cache[$key])) {
			$lines = &$cache[$key];
		} else {
			$lines = $this->shcmd("ls " . implode(" ", $packages), $rc);
			if ($rc) {
				# try for each package separately to catch errors
				# https://bugs.launchpad.net/poldek/+bug/1024970
				$lines = array();
				foreach ($packages as $p) {
					$lines = array_merge($lines, $this->shcmd("ls " . $p));
				}
			}
			$cache[$key] = &$lines;
		}

		foreach ($lines as &$line) {
			if (preg_match('/^(?P<name>.+)-(?P<version>[^-]+)-(?P<release>[^-]+)\.(?P<arch>[^.]+)$/', $line, $m)) {
				if ($m['name'] == $package) {
					return $line;
				}
			} elseif (preg_match('/error: (?P<name>.+): no such package or directory/', $line, $m)) {
				if ($m['name'] == $package) {
					return $line;
				}
			}
		}
	}

	/**
	 * Query poldek database
	 */
	public function shcmd($cmd, &$rc = null) {
		return $this->exec('-Q --shcmd='.escapeshellarg($cmd), $rc);
	}

	private function exec($cmd, &$rc = null) {
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

		exec($poldek, $lines, $rc);
		return $lines;
	}
}

//Setup VIM: ex: noet ts=4 enc=utf-8 :

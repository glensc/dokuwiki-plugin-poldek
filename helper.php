<?php
/**
 * Poldek Plugin: query poldek for package info
 *
 * Add poldek tags to dokuwiki
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Elan Ruusamäe <glen@delfi.ee>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * @author     Elan Ruusamäe <glen@delfi.ee>
 */
class helper_plugin_poldek extends DokuWiki_Plugin {
	/*
	 * @var cache $cache
	 */
	private $cache;
	/**
	 * @var bool $cache_ok
	 */
	private $cache_ok;

	public function __construct() {
		global $conf;
		$this->cache = new cache($this->getPluginName(), '.txt');
		$this->cache_ok = $this->cache->useCache(array('age' => $conf['locktime']));
	}

	/**
	 * Update poldek indexes for active repos
	 * Save down list of packages.
	 */
	public function sync($force = false) {
		if (!$this->cache_ok) {
			// without force update indexes only if cache is missing
			if ($force || !file_exists($this->cache->cache)) {
				$this->exec("--up");
				$lines = $this->shcmd("ls", $rc);
				$this->cache->storeCache(join("\n", $lines));
			}
		}
	}

	public function ls($package) {
		global $conf;

		$this->sync();
		$lines = explode("\n", $this->cache->retrieveCache(false));

		foreach ($lines as $line) {
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

		return "error: $package: no such package";
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

		error_log($poldek);
		exec($poldek, $lines, $rc);
		return $lines;
	}
}

//Setup VIM: ex: noet ts=4 enc=utf-8 :

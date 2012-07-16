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
	 * Path to package list cache file
	 * Filled by sync() method.
	 * @var string $cache
	 */
	private $cache;

	/**
	 * Update poldek indexes for active repos
	 * Save down list of packages.
	 *
	 * We create two cache objects, one for updating indexes, which is mtime based
	 * And other for package list, which depends on index file.
	 * And each page depends on the package list file
	 *
	 * Without force, cache is attempted to be used, even if it's stale
	 *
	 * Called by ls command, or from cron
	 */
	public function sync($force = false) {
		global $conf;
		$repos = $this->getConf('repos');

		$idx_cache = new cache($this->getPluginName() . $repos, '.idx');
		$pkg_cache = new cache($this->getPluginName() . $repos, '.txt');
		$cache_exists = file_exists($pkg_cache->cache);

		// check poldek indexes
		if (!$cache_exists || !$idx_cache->useCache(array('age' => $conf['locktime']))) {
			// without force update indexes only if cache is missing
			if ($force || !$cache_exists) {
				$lines = $this->exec("--up");
				// process output, if we find "Writing ..." line, means we should update ls output as well
				// Writing /root/.poldek-cache/[...]/packages.ndir.gz...
				if (!$cache_exists || preg_grep('/^Writing /', $lines)) {
					$idx_cache->storeCache(time());
				} else {
					// freshen timestamp or we keep updating indexes if index
					// is older than locktime
					touch($idx_cache->cache);
					if ($force) {
						// sleep, so packages cache be newer
						sleep(1);
					}
					// touch also package file, not to trigger it's update
					touch($pkg_cache->cache);
					clearstatcache();
				}
			}
		}

		// do not update listing, if cache exists and not in cron mode
		if (($force || !$cache_exists) && !$pkg_cache->useCache(array('files' => array($idx_cache->cache)))) {
			$lines = $this->shcmd("ls");
			$pkg_cache->storeCache(join("\n", $lines));
		}

		$this->cache = $pkg_cache->cache;
	}

	public function ls($package) {
		$this->sync();

		foreach (file($this->cache) as $line) {
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
		return $this->exec('-q --skip-installed -Q --shcmd='.escapeshellarg($cmd), $rc);
	}

	private function exec($cmd, &$rc = null) {
		global $conf;
		$cachedir = $conf['cachedir'].'/'.$this->getPluginName();

		// base poldek command
		$poldek = 'exec poldek -O cachedir=' . escapeshellarg($cachedir);

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

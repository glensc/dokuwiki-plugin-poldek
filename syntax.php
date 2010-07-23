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
$conf['plugin']['poldek']['cachedir'] = '/tmp/dw-poldek';
$conf['plugin']['poldek']['http_proxy'] = 'http://proxy.delfi.lan:3128/';
$conf['plugin']['poldek']['ftp_proxy'] = 'http://proxy.delfi.lan:3128/';
$conf['plugin']['poldek']['repos'] = 'dsl';
$conf['plugin']['poldek']['debug'] = true;
 */

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_poldek extends DokuWiki_Syntax_Plugin {

    /**
     * return some info
     */
    function getInfo() {
      return array(
        'author' => 'Elan Ruusamäe',
        'email'  => 'glen@delfi.ee',
        'date'   => '2009-01-29',
        'name'   => 'Poldek Plugin',
        'desc'   => 'Plugin to display package version info from repositories',
        'url'    => 'https://cvs.delfi.ee/dokuwiki/plugin/poldek/',
      );
    }

    /**
     * What kind of syntax are we?
     */
    function getType() {
        return 'substition';
    }

    /**
     * Where to sort in?
     */
    function getSort() {
        return 306;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{poldek>.+?\}\}', $mode, 'plugin_poldek');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
		$raw = substr($match, 9, -2);

        $data = array('pkg' => $raw);
        return $data;
    }

    /**
     * Create output
     */
    function render($format, &$renderer, $data) {
        global $ID;
        if ($format != 'xhtml') {
            return false;
        }

        global $conf;
        // get plugin config
        $c = empty($conf['plugin']['poldek']) ? array() : $conf['plugin']['poldek'];

        $cachedir = !empty($c['cachedir']) ? $c['cachedir'] : '/tmp/dw-poldek';

        // base poldek command
        $poldek = 'poldek -q --skip-installed -O cachedir='.escapeshellarg($cachedir);

        if ($c['repos']) {
            foreach (explode(',', $c['repos']) as $repo) {
                $poldek .= ' --sn '.escapeshellarg(trim($repo));
            }
        }

        static $sync = false;
        if (!$sync) {
            // sync indexes once per page
            $cmd = "$poldek --up";
            // proxies setup
            if (!empty($c['http_proxy'])) {
                $cmd .= ' -O '.escapeshellarg("proxy=http: {$c['http_proxy']}");
            }
            if (!empty($c['ftp_proxy'])) {
                $cmd .= ' -O '.escapeshellarg("proxy=ftp: {$c['ftp_proxy']}");
            }

            if ($c['debug']) {
                error_log($cmd);
            }
            exec($cmd, $lines, $rc);
            $sync = true;
        }

        $cmd = $poldek.' -Q --shcmd='.escapeshellarg("ls {$data['pkg']}");
        if ($c['debug']) {
            error_log($cmd);
        }
        exec($cmd, $lines, $rc);
        if ($c['debug']) {
            error_log("got[".join('\n', $lines)."] -> $rc");
        }

        if (!$rc) {
            $renderer->doc .= join("\n", $lines);
        }

        return true;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :

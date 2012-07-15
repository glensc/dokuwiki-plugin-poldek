<?php
/**
 * Metadata for configuration manager plugin
 * Additions for the Poldek plugin
 *
 * @author     Elan Ruusamäe <glen@delfi.ee>
 */
$conf['cachedir'] = array('string');
$conf['http_proxy'] = array('string', '_pattern' => '{^(?:$|https?://\S+$)}i');
$conf['ftp_proxy'] = array('string', '_pattern' => '{^(?:$|https?://\S+$)}i');
$conf['repos'] = array('string');

//Setup VIM: ex: noet ts=4 enc=utf-8 :

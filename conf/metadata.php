<?php
/**
 * Metadata for configuration manager plugin
 * Additions for the Poldek plugin
 *
 * @author     Elan Ruusamäe <glen@delfi.ee>
 */
$meta['cachedir'] = array('string');
$meta['http_proxy'] = array('string', '_pattern' => '{^(?:$|https?://\S+$)}i');
$meta['ftp_proxy'] = array('string', '_pattern' => '{^(?:$|https?://\S+$)}i');
$meta['repos'] = array('string');

//Setup VIM: ex: noet ts=4 enc=utf-8 :

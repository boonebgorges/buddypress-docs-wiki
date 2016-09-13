<?php
/*
Plugin Name: BuddyPress Docs Wiki add-on
Description: Add a separate Wiki section to your site. Powered by BuddyPress Docs
Author: Boone B Gorges
Author URI: http://boone.gorg.es
License: GPLv3
Version: 1.0.10
*/

define( 'BPDW_VERSION', '1.0.10' );

function bpdw_init() {
	require dirname(__FILE__) . '/bpdw.php';
}
add_action( 'bp_docs_load', 'bpdw_init' );


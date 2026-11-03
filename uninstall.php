<?php
/**
 * Remove options on uninstall.
 *
 * @package WpAiDisclosure
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option( 'wp_ai_disclosure' );
delete_option( 'wp_ai_disclosure_log' );

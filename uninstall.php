<?php
/**
 * The plugin's uninstall script.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$options = 'adblade_options';

delete_option( $options );

delete_site_option( $options );

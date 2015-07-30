<?php
/**
 * Plugin Name: Revision Strike
 * Plugin URI: https://stevegrunwell.com/revision-strike
 * Description: Periodically purge old post revisions via WP Cron.
 * Version: 0.1
 * Author: Steve Grunwell
 * Author URI: https://stevegrunwell.com
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

require_once dirname( __FILE__ ) . '/includes/class-revision-strike.php';
require_once dirname( __FILE__ ) . '/includes/class-revision-strike-cli.php';
require_once dirname( __FILE__ ) . '/includes/class-settings.php';

/**
 * Bootstrap the plugin.
 */
function revisionstrike_init() {
	new RevisionStrike;
}

add_action( 'init', 'revisionstrike_init' );

/**
 * Register the cron job on plugin activation.
 */
function revisionstrike_register_cron() {
	if ( false === wp_next_scheduled( RevisionStrike::STRIKE_ACTION ) ) {
		wp_schedule_event( time(), 'daily', RevisionStrike::STRIKE_ACTION );
	}
}

register_activation_hook( __FILE__, 'revisionstrike_register_cron' );

/**
 * Cancel the cron job when the plugin is disabled.
 */
function revisionstrike_deregister_cron() {
	wp_clear_scheduled_hook( RevisionStrike::STRIKE_ACTION );
}

register_deactivation_hook( __FILE__, 'revisionstrike_deregister_cron' );
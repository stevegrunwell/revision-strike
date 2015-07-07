<?php
/**
 * Mock class for WP-CLI.
 */

if ( class_exists( '\WP_CLI' ) || class_exists( '\WP_CLI_Command' ) ) {
	return;
}

class WP_CLI {

	public static function add_command() {}
	public static function line() {}
	public static function log() {}
	public static function success() {}
	public static function error() {}

}

class WP_CLI_Command {

}
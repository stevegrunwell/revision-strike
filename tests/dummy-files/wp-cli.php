<?php
/**
 * Mock class for WP-CLI.
 *
 * Sadly, WP_CLI uses a lot of public, static methods for things like logging. Great when you're
 * writing the code, but a real pain in the butt to test.
 *
 * This test-double for WP_CLI uses the __callStatic magic method paired with a Singleton pattern
 * to effectively convert static method calls to non-static ones (albeit with a "_" prefix).
 *
 * Calling WP_CLI::getInstance() will return a Mockery-powered mock of the class, to which you can
 * attach assertions. Be sure to write your assertions based on the method *with a leading
 * underscore!*
 *
 * Example:
 *
 * public function testHelloWorld() {
 *   $cli = WP_CLI::getInstance();
 *   $cli->shouldReceive( '_success' )
 *     ->once()
 *     ->with( 'Hello world!' );
 * }
 *
 * Be sure to call WP_CLI::tearDown() to reset the Singleton after each test to prevent your tests
 * from getting polluted with old data!
 */

if ( class_exists( '\WP_CLI' ) || class_exists( '\WP_CLI_Command' ) ) {
	return;
}

class WP_CLI {

	public static $instance;

	public static function __callStatic( $name, $args ) {
		$instance = self::getInstance();
		return call_user_func_array( array( $instance, 'calledStatic' ), func_get_args() );
	}

	public static function getInstance() {
		if ( null === static::$instance ) {
			static::$instance = \Mockery::mock( __CLASS__ )
				->shouldAllowMockingProtectedMethods()
				->makePartial();
		}
		return static::$instance;
	}

	/**
	 * Here's something you don't often see in a Singleton: something to destroy it!
	 */
	public static function tearDown() {
		static::$instance = null;
	}

	public function calledStatic( $name, $args ) {
		$method = '_' . $name;
		$this->$method( $args );
	}

	protected function _add_command() {}
	protected function _line() {}
	protected function _log() {}
	protected function _success() {}
	protected function _error() {}

}

class WP_CLI_Command {

}
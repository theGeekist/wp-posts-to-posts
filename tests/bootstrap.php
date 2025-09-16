<?php

// Define required WP test constants early so anything pulled in by the
// Composer autoloader (or wp-phpunit internals) can rely on them.
// If a local wp-tests config isn't provided globally, point WP to our minimal one.
putenv('WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php');

// Constants will be loaded from wp-tests-config.php. Provide fallbacks only if absent after load.

// Force admin context (WordPress provides is_admin itself; set a flag instead)
define( 'P2P_TESTS_FORCE_ADMIN', true );

// Load Composer autoloader so that yoast/phpunit-polyfills and other
// dev dependencies are available before the WP test suite boots.
\call_user_func(function() {
	$autoload = dirname(__DIR__) . '/vendor/autoload.php';
	if ( file_exists( $autoload ) ) {
		require_once $autoload;
	}
});

// Register plugin loader BEFORE bootstrapping WordPress so that it is treated as an mu-plugin
// (ensuring constants like P2P_TEXTDOMAIN are defined before other code referencing them runs).

$_tests_dir = getenv('WP_TESTS_DIR');
if ( ! $_tests_dir ) {
	$vendorDir = dirname( __DIR__ ) . '/vendor';
	$candidate = $vendorDir . '/wp-phpunit/wp-phpunit';
	$_tests_dir = is_dir( $candidate . '/includes' ) ? $candidate : '/tmp/wordpress-tests-lib';
}
if ( ! file_exists( $_tests_dir . '/includes/bootstrap.php' ) ) {
	fwrite( STDERR, "WordPress test suite bootstrap not found at $_tests_dir. Set WP_TESTS_DIR env var.\n" );
	exit(1);
}
require $_tests_dir . '/includes/bootstrap.php';

// Load plugin directly (simpler than relying on early hooks in legacy code during tests)
if ( ! defined( 'P2P_PLUGIN_LOADED_FOR_TESTS' ) ) {
	define( 'P2P_PLUGIN_LOADED_FOR_TESTS', true );
	require dirname( __FILE__ ) . '/../posts-to-posts.php';
	if ( function_exists( '_p2p_load' ) ) {
		_p2p_load();
	}
	if ( function_exists( '_p2p_init' ) ) {
		_p2p_init();
	}
	if ( file_exists( dirname( __FILE__ ) . '/../debug-utils.php' ) ) {
		require dirname( __FILE__ ) . '/../debug-utils.php';
	}
}

// Back-compat alias for legacy constraint class name used in constraints.php
if ( ! class_exists( 'PHPUnit_Framework_Constraint' ) && class_exists( '\PHPUnit\Framework\Constraint\Constraint' ) ) {
	abstract class PHPUnit_Framework_Constraint extends \PHPUnit\Framework\Constraint\Constraint {
		// Provide default implementations; legacy subclass overrides as needed.
		public function toString(): string { return 'legacy constraint'; }
		public function matches($other): bool { return false; }
	}
}


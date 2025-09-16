<?php
// Minimal config for wp-phpunit when not using a global installation.
define( 'DB_NAME', 'wp_test' );
define( 'DB_USER', 'wp_test_user' );
define( 'DB_PASSWORD', 'testpass' );
define( 'DB_HOST', '127.0.0.1' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
define( 'WP_TESTS_DOMAIN', 'local.geekist.co' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', PHP_BINARY );
define( 'ABSPATH', dirname( __DIR__ ) . '/wordpress/' );
// Table prefix required by test suite bootstrap.
$table_prefix = 'wptests_';
// Use the built-in WP debug constants to surface issues in tests.
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', true );
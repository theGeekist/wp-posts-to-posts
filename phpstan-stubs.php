<?php
// Minimal stubs for PHPStan analysis when run outside of WP bootstrap.
if (!class_exists('WP_Widget')) { abstract class WP_Widget { public function __construct() {} } }
if (!function_exists('add_action')) { function add_action($hook, $callback, $priority = 10, $args = 1) {} }
if (!function_exists('register_uninstall_hook')) { function register_uninstall_hook($file, $callback) {} }
if (!function_exists('is_admin')) { function is_admin() { return false; } }
if (!function_exists('load_plugin_textdomain')) { function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = '') {} }
if (!function_exists('plugins_url')) { function plugins_url($path = '', $plugin = '') { return $path; } }
if (!function_exists('admin_url')) { function admin_url($path = '') { return $path; } }
if (!function_exists('wp_create_nonce')) { function wp_create_nonce($action = -1) { return 'nonce'; } }
if (!function_exists('__')) { function __($text, $domain = null) { return $text; } }
if (!function_exists('current_user_can')) { function current_user_can($cap) { return true; } }

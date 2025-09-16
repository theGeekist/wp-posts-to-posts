<?php

// Ensure admin context so that plugin bootstrap loads admin pieces.
if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() { return true; }
}

require_once __DIR__ . '/constraints.php';
// mock-factory will be loaded lazily in setUp once admin classes are present.


class P2P_Tests_Admin_Factory extends WP_UnitTestCase {
	/** @var P2P_Factory_Mock */
	private $mock;

	function setUp(): void {
		parent::setUp();
		// Ensure admin autoload is registered (in case plugin init not invoked yet)
		if ( function_exists( '_p2p_load_admin' ) && ! class_exists( 'P2P_Factory' ) ) {
			_p2p_load_admin();
		}
		// Fallback: register autoload directly if still missing
		if ( ! class_exists( 'P2P_Factory' ) && class_exists( 'P2P_Autoload' ) ) {
			P2P_Autoload::register( 'P2P_', dirname( __DIR__ ) . '/admin' );
		}
		if ( ! class_exists( 'P2P_Factory_Mock' ) ) {
			require_once __DIR__ . '/mock-factory.php';
		}
		$this->mock = new P2P_Factory_Mock;
	}

	function test_factory_none() {
		$ctype = p2p_register_connection_type( array(
			'name' => __FUNCTION__,
		) );

		$this->assertEmpty( $this->mock->get_queue() );
	}

	function test_factory_any() {
		$ctype = p2p_register_connection_type( array(
			'name' => __FUNCTION__,
			'from' => 'user',
			'to' => 'page',
			'admin_mock' => 'any',
		) );

		$this->assertNotEmpty( $this->mock->get_queue() );

		$this->assertEquals( 0, count( $this->mock->add_items( 'post', 'post' ) ) );

		$this->assertEquals( 1, count( $this->mock->add_items( 'post', 'page' ) ) );

		$this->assertEquals( 1, count( $this->mock->add_items( 'user' ) ) );
	}

	function test_factory_from() {
		$ctype = p2p_register_connection_type( array(
			'name' => __FUNCTION__,
			'from' => 'user',
			'to' => 'page',
			'admin_mock' => 'from',
		) );

		$this->assertNotEmpty( $this->mock->get_queue() );

		$this->assertEquals( 0, count( $this->mock->add_items( 'post', 'post' ) ) );

		$this->assertEquals( 0, count( $this->mock->add_items( 'post', 'page' ) ) );

		$this->assertEquals( 1, count( $this->mock->add_items( 'user' ) ) );
	}

	function test_factory_to() {
		$ctype = p2p_register_connection_type( array(
			'name' => __FUNCTION__,
			'from' => 'user',
			'to' => 'page',
			'admin_mock' => 'to',
		) );

		$this->assertNotEmpty( $this->mock->get_queue() );

		$this->assertEquals( 0, count( $this->mock->add_items( 'post', 'post' ) ) );

		$this->assertEquals( 1, count( $this->mock->add_items( 'post', 'page' ) ) );

		$this->assertEquals( 0, count( $this->mock->add_items( 'user' ) ) );
	}

	function test_factory_extra_args() {
		$ctype = p2p_register_connection_type( array(
			'name' => __FUNCTION__,
			'from' => 'user',
			'to' => 'page',
			'admin_mock' => array(
				'foo' => 'bar',
			),
		) );

		$this->assertNotEmpty( $this->mock->get_queue() );

		$this->assertEquals( 0, count( $this->mock->add_items( 'post', 'post' ) ) );

		$this->assertEquals( 1, count( $this->mock->add_items( 'post', 'page' ) ) );

		$this->assertEquals( 1, count( $this->mock->add_items( 'user' ) ) );
	}
}


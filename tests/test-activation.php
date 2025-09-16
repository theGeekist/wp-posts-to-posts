<?php

class P2P_Activation_Test extends WP_UnitTestCase {
	public function test_plugin_loaded_functions_exist() {
		$this->assertTrue( function_exists( 'p2p_register_connection_type' ), 'p2p_register_connection_type() should exist after plugin load.' );
	}
}

<?php

class P2P_Factory_Mock extends P2P_Factory {

	protected $key = 'admin_mock';

	protected $added_items = array();

	function get_queue( $key = null ) {
		if ( null === $key )
			return $this->queue;

		return $this->queue[ $key ];
	}

	function add_item( $directed, $object_type, $post_type, $title ) {
		$this->added_items[] = func_get_args();
	}

	function add_items( $object_type = null, $post_type = null ) {
		if ( null === $object_type ) {
			// Mirror parent fallback if called without parameters (not used in tests but keeps parity)
			parent::add_items();
		} else {
			$this->filter( $object_type, $post_type );
		}

		$r = $this->added_items;

		$this->added_items = array();

		return $r;
	}
}


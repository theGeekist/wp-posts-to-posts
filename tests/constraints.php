<?php

class P2P_Constraint extends PHPUnit_Framework_Constraint {
	/** @var string */
	private $desc;
	/** @var callable */
	private $test;

	public function __construct( string $description, callable $test_cb ) {
		$this->desc = $description;
		$this->test = $test_cb;
	}

	public function matches( $arg ): bool {
		return (bool) call_user_func( $this->test, $arg );
	}

	public function toString(): string {
		return $this->desc;
	}
}


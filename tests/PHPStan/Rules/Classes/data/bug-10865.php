<?php

namespace Bug10865;

class TestParent {

	/** @param array<string|int, mixed> $args */
	public function __construct(array $args) {

		var_dump($args);
	}
}

class Test extends TestParent {

	public function __construct(int $a) {

		parent::__construct(get_defined_vars());
		//parent::__construct(func_get_args());
	}
}

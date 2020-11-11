<?php

namespace Bug1917;

class A {
	private $a;
	private $b;

	public function __construct($a, $b)
	{
		$this->a = $a;
		$this->b = $b;

		var_dump([$this->a, $this->b]);
	}
}

class B extends A {
	function __construct($a, $b) {
		parent::__construct(...func_get_args());
	}
}

<?php

namespace Bug12676;

/**
 * @immutable
 */
class A {

	/** @var array<string, int> */
	public array $a;

	public function __construct() {
		$this->a = ['b' => 2, 'a' => 1];
		ksort($this->a);
	}
}

class B {
	/** @readonly */
	public array $readonlyArr;

	public function __construct() {
		$this->readonlyArr = ['b' => 2, 'a' => 1];
		ksort($this->readonlyArr);
	}
}

class C {
	/** @readonly */
	static public array $readonlyArr;

	public function __construct() {
		self::$readonlyArr = ['b' => 2, 'a' => 1];
		ksort(self::$readonlyArr);
	}
}

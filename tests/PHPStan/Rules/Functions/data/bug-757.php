<?php

namespace Bug757;

class A {
	/** @var string */
	public $foo = "bar";

	public function &getString() : string {
		return $this->foo;
	}

	public function getStringNoRef() : string {
		return $this->foo;
	}

	public static function &staticGetString() : string {
		static $s = "bar";
		return $s;
	}
}

function &refFunction() : string {
	static $s = "bar";
	return $s;
}

function noRefFunction() : string {
	return "bar";
}

function useString(string &$s) : void {}

function () {
	$a = new A();
	useString($a->getString()); // ok - returns by reference
	useString($a->getStringNoRef()); // error - does not return by reference
	useString(A::staticGetString()); // ok - returns by reference
	useString(refFunction()); // ok - returns by reference
	useString(noRefFunction()); // error - does not return by reference
};

<?php

namespace PassedByReference;

function foo(&$foo)
{

}

function &getRef(): string
{
	static $ref = 'testing';

	return $ref;
}

class Bar
{

	private $barProperty;

	private static $staticBarProperty;

	public function doBar()
	{
		$bar = 'Bar';

		foo($this->barProperty); // ok
		foo(self::$staticBarProperty); // ok
		foo($this->getInstanceRef()); // ok
		foo(self::getStaticRef()); // ok
		foo($bar::getStaticRef()); // ok

		$method = [$bar, 'getStaticRef'];
		foo($method());
		$method = "$bar::getStaticRef";
		foo($method());
	}

	public function &getInstanceRef()
	{
		return $this->barProperty;
	}

	public static function &getStaticRef()
	{
		return self::$staticBarProperty;
	}

}

function () {
	$i = 0;
	foo($i); // ok

	$arr = [1, 2, 3];
	foo($arr[0]); // ok

	foo(getRef()); // ok

	foo(rand());
	foo(null);

	$m = null;
	preg_match('a', 'b', $m);

	$n = null;
	reset($n);
};

function bar(string &$s) {

}

function () {
	$i = 1;
	bar($i);
};

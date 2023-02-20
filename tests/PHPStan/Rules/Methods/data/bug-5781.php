<?php

namespace Bug5781;

class Foo {

	/**
	 * @param array{a: bool, b: bool, c: bool, d: bool, e: bool, f: bool, g: bool, h: bool, i: bool, j: bool, k: bool, l: bool, m: bool, n: bool, o: bool, p: bool} $param
	 */
	public static function bar(array $param): bool
	{
		return true;
	}

	public function doFoo()
	{
		Foo::bar([]);
	}

}

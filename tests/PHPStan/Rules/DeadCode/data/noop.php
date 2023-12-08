<?php

namespace DeadCodeNoop;

function (stdClass $foo, bool $a, bool $b) {
	$foo->foo();

	$arr = [];
	$arr;
	$arr['test'];
	$foo::$test;
	$foo->test;

	'foo';
	1;

	@'foo';
	+1;
	-1;

	+$foo->foo();
	-$foo->foo();
	@$foo->foo();

	isset($test);
	empty($test);
	true;
	Foo::TEST;

	(string) 1;

	$r = $a xor $b;

	$s = $a and doFoo();
	$t = $a and $b;

	$s = $a or doFoo();
	$t = $a or $b;

	$a ? $b : $s;
	$a ?: $b;
	$a ? doFoo() : $s;
	$a ? $b : doFoo();
	$a ? doFoo() : doBar();

	$a || $b;
	$a || doFoo();

	$a && $b;
	$a && doFoo();
};

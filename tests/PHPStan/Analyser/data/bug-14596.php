<?php

namespace Bug14596;

function foo(int $a, int $b, int $c, string ...$rest): void {}

class Foo {
	public function bar(int $a, int $b, int $c, string ...$rest): void {}
	public static function baz(int $a, int $b, int $c, string ...$rest): void {}
	public function __construct(int $a, int $b, int $c, string ...$rest) {}
}

// built-in function
\PHPStan\dumpType(1, 2, 3, d: 'foo', 5);

// user-defined function
foo(1, 2, 3, d: 'foo', 5);

// method call
$obj = new Foo(1, 2, 3);
$obj->bar(1, 2, 3, d: 'foo', 5);

// static method call
Foo::baz(1, 2, 3, d: 'foo', 5);

// constructor
new Foo(1, 2, 3, d: 'foo', 5);

// closure
$closure = function (int $a, int $b, int $c, string ...$rest): void {};
$closure(1, 2, 3, d: 'foo', 5);

// call_user_func
call_user_func('Bug14596\foo', 1, 2, 3, d: 'foo', 5);

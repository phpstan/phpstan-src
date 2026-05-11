<?php

namespace Bug14596Methods;

class Foo {
	public function bar(int $a, int $b, int $c, string ...$rest): void {}
	public static function baz(int $a, int $b, int $c, string ...$rest): void {}
}

function (Foo $obj): void {
	$obj->bar(1, 2, 3, d: 'foo', 5);
	Foo::baz(1, 2, 3, d: 'foo', 5);
};

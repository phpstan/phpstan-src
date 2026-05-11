<?php

namespace Bug14596;

function foo(int $a, int $b, int $c, string ...$rest): void {}

class Foo {
	public function bar(int $a, int $b, int $c, string ...$rest): void {}
	public static function baz(int $a, int $b, int $c, string ...$rest): void {}
	public function __construct(int $a, int $b, int $c, string ...$rest) {}
}

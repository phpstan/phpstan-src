<?php
// php-parser 5.9: "?" argument placeholders (ArgPlaceholder) and "..." in any
// argument position (VariadicPlaceholder) — PHP 8.6 partial function application
$f = strlen(?);
$f = foo(1, ?);
$f = foo(?, ...);
$f = foo(name: ?);
$f = foo(1, ?, name: ?, ...);
$f = foo(?, ?, ?);
$f = foo(...);
$f = foo(..., );
$f = $obj->foo(?);
$f = $obj->foo(1, ...);
$f = A::foo(?);
$f = static::foo(value: ?);
$f = $obj::{'foo'}(?);
$f = $callable(?, 2);
$f = foo(?)(?);
$f = foo(bar(?), ?);

// invalid, but accepted on the parser level
$f = new Foo(?);
$f = new Foo(...);
$f = new class(?) {};
$f = $obj?->foo(?);
$f = $obj?->foo(...);
$f = foo(..., 1);
$f = foo(?,);
$f = exit(?);
$f = die(name: ?);
$f = exit(...);
$f = clone($x, ?);
$f = clone(?);

#[Foo(?)]
function foo() {}

#[Foo(name: ?, ...)]
class Bar {}

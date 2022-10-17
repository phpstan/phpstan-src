<?php

namespace ParamOut;

use function PHPStan\Testing\assertType;

/**
 * @param-out string $s
 */
function addFoo(?string &$s): void
{
	if ($s === null) {
		$s = "hello";
	}
	$s .= "foo";
}

/**
 * @template T of int
 * @param-out T $s
 */
function genericFoo(mixed &$s): void
{
}

/**
 * @template T of int
 */
class FooBar {
	/**
	 * @param-out T $s
	 */
	function genericClassFoo(mixed &$s): void
	{
	}

	/**
	 * @template S of self
	 * @param-out S $s
	 */
	function genericSelf(mixed &$s): void
	{
	}

	/**
	 * @template S of static
	 * @param-out S $s
	 */
	function genericStatic(mixed &$s): void
	{
	}
}

/**
 * @param-out bool $s
 */
function takesNullableBool(?bool &$s) : void {
	$s = true;
}

/**
 * @param-out int $var
 */
function variadicFoo(&...$var)
{
	$var[0] = 2;
	$var[1] = 2;
}

/**
 * @param-out string $s
 * @param-out int $var
 */
function variadicFoo2(?string &$s, &...$var)
{
	$s = '';
	$var[0] = 2;
	$var[1] = 2;
}

function foo1(?string $s) {
	assertType('string|null', $s);
	addFoo($s);
	assertType('string', $s);
}

function foo2($mixed) {
	assertType('mixed', $mixed);
	addFoo($mixed);
	assertType('string', $mixed);
}

function foo3($mixed) {
	assertType('mixed', $mixed);
	$fooBar = new FooBar();
	$fooBar->genericClassFoo($mixed);
	assertType('T of int (class ParamOut\FooBar, parameter)', $mixed);
}

function foo4($mixed) {
	assertType('mixed', $mixed);
	$fooBar = new FooBar();
	$fooBar->genericSelf($mixed);
	assertType('S of ParamOut\FooBar (method ParamOut\FooBar::genericSelf(), parameter)', $mixed);
}

function foo5($mixed) {
	assertType('mixed', $mixed);
	$fooBar = new FooBar();
	$fooBar->genericStatic($mixed);
	assertType('S (method ParamOut\FooBar::genericStatic(), parameter)', $mixed);
}

function foo6() {
	$b = false;
	takesNullableBool($b);

	assertType('bool', $b);
}

function foo7() {
	variadicFoo( $a, $b);
	assertType('int', $a);
	assertType('int', $b);

	variadicFoo2($s, $a, $b);
	assertType('string', $s);
	assertType('int', $a);
	assertType('int', $b);
}

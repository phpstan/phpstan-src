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


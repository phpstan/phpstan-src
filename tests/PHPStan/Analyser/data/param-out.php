<?php

namespace ParamOut;

use function PHPStan\Testing\assertType;
use sodium_memzero;

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
 */
class FooBar {
	/**
	 * @param-out T $s
	 */
	function genericClassFoo(mixed &$s): void
	{
	}

	/**
	 * @param-out string $s
	 */
	function baseMethod(?string &$s): void
	{
	}

	function overriddenMethod(?string &$s): void
	{
	}

	/**
	 * @param-out string $s
	 */
	function overriddenButinheritedPhpDocMethod(?string &$s): void
	{
	}
}

/**
 * @extends FooBar<int, ExtendsFooBar>
 */
class ExtendsFooBar extends FooBar {
	/**
	 * @param-out string $s
	 */
	function subMethod(?string &$s): void
	{
	}

	/**
	 * @param-out string $s
	 */
	function overriddenMethod(?string &$s): void
	{
	}

	function overriddenButinheritedPhpDocMethod(?string &$s): void
	{
	}
}

class OutFromStub {
	function stringOut(string &$string): void
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
function variadicFoo(&...$var): void
{
	$var[0] = 2;
	$var[1] = 2;
}

/**
 * @param-out string $s
 * @param-out int $var
 */
function variadicFoo2(?string &$s, &...$var): void
{
	$s = '';
	$var[0] = 2;
	$var[1] = 2;
}

function foo1(?string $s): void {
	assertType('string|null', $s);
	addFoo($s);
	assertType('string', $s);
}

function foo2($mixed): void {
	assertType('mixed', $mixed);
	addFoo($mixed);
	assertType('string', $mixed);
}

/**
 * @param FooBar<int> $fooBar
 * @return void
 */
function foo3($mixed, $fooBar): void {
	assertType('mixed', $mixed);
	$fooBar->genericClassFoo($mixed);
	assertType('T of int (class ParamOut\FooBar, parameter)', $mixed);
}

function foo6(): void {
	$b = false;
	takesNullableBool($b);

	assertType('bool', $b);
}

function foo7(): void {
	variadicFoo( $a, $b);
	assertType('int', $a);
	assertType('int', $b);

	variadicFoo2($s, $a, $b);
	assertType('string', $s);
	assertType('int', $a);
	assertType('int', $b);
}

function foo8(string $s): void {
	sodium_memzero($s);
	assertType('null', $s);
}

function foo9(?string $s): void {
	$c = new OutFromStub();
	$c->stringOut($s);
	assertType('string', $s);
}

function foo10(?string $s): void {
	$c = new ExtendsFooBar();
	$c->baseMethod($s);
	assertType('string', $s);
}

function foo11(?string $s): void {
	$c = new ExtendsFooBar();
	$c->subMethod($s);
	assertType('string', $s);
}

function foo12(?string $s): void {
	$c = new ExtendsFooBar();
	$c->overriddenMethod($s);
	assertType('string', $s);
}

function foo13(?string $s): void {
	$c = new ExtendsFooBar();
	$c->overriddenButinheritedPhpDocMethod($s);
	assertType('string', $s);
}

/**
 * @param array<string> $a
 * @param non-empty-array<string> $nonEmptyArray
 */
function foo14(array $a, $nonEmptyArray): void {
	// php-src native function, overridden from stub

	\shuffle($a);
	assertType('list<string>', $a);
	\shuffle($nonEmptyArray);
	assertType('non-empty-list<string>', $nonEmptyArray);
}

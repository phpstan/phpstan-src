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

	/**
	 * @param-out string $b
	 */
	public function renamedParams(int $a, int &$b) {
	}

	/**
	 * @param-out string $b
	 */
	public function paramOutOverridden(int $a, int &$b) {
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

	public function renamedParams(int $x, int &$y) {
		parent::renamedParams($x, $y);
	}

	/**
	 * @param-out array $b
	 */
	public function paramOutOverridden(int $a, int &$b) {
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
	assertType('int', $mixed);
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
	\shuffle($a);
	assertType('list<string>', $a);
	\shuffle($nonEmptyArray);
	assertType('non-empty-list<string>', $nonEmptyArray);
}

function fooCompare (int $a, int $b): int {
	return $a > $b ? 1 : -1;
}

function foo15() {
	$manifest = [1, 2, 3];
	uasort(
		$manifest,
		"fooCompare"
	);
	assertType('array{1, 2, 3}', $manifest);
}

function fooSpaceship (string $a, string $b): int {
	return $a <=> $b;
}

function foo16() {
	$array = [1, 2];
	uksort(
		$array,
		"fooSpaceship"
	);
	assertType('array{1, 2}', $array);
}

function fooShuffle() {
	$array = ["foo" => 123, "bar" => 456];
	shuffle($array);
	assertType('non-empty-array<0|1, 123|456>&list', $array);

	$emptyArray = [];
	shuffle($emptyArray);
	assertType('array{}', $emptyArray);
}

function fooSort() {
	$array = ["foo" => 123, "bar" => 456];
	sort($array);
	assertType('array{foo: 123, bar: 456}', $array);

	$emptyArray = [];
	sort($emptyArray);
	assertType('array{}', $emptyArray);
}

function fooScanf(): void
{
	sscanf("10:05:03", "%d:%d:%d", $hours, $minutes, $seconds);
	assertType('float|int|string|null', $hours);
	assertType('float|int|string|null', $minutes);
	assertType('float|int|string|null', $seconds);

	$n = sscanf("42 psalm road", "%s %s", $p1, $p2);
	assertType('int|null', $n); // could be 'int'
	assertType('float|int|string|null', $p1);
	assertType('float|int|string|null', $p2);
}

function fooMatch(string $input): void {
	preg_match_all('/@[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)/', $input, $matches, PREG_PATTERN_ORDER);
	assertType('array<list<string>>', $matches);

	preg_match_all('/@[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)/', $input, $matches, PREG_SET_ORDER);
	assertType('list<array<string>>', $matches);

	preg_match('/@[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)/', $input, $matches, PREG_UNMATCHED_AS_NULL);
	assertType("array<string|null>", $matches);
}

function fooParams(ExtendsFooBar $subX, float $x1, float $y1)
{
	$subX->renamedParams($x1, $y1);

	assertType('float', $x1);
	assertType('string', $y1); // overridden via reference of base-class, by param order (renamed params)
}

function fooParams2(ExtendsFooBar $subX, float $x1, float $y1) {
	$subX->paramOutOverridden($x1, $y1);

	assertType('float', $x1);
	assertType('array', $y1); // overridden phpdoc-param-out-type in subclass
}

function fooDateTime(\SplFileObject $splFileObject, ?string $wouldBlock) {
	// php-src native method overridden via stub
	$splFileObject->flock(1, $wouldBlock);

	assertType('string', $wouldBlock);
}

function testMatch() {
	preg_match('#.*#', 'foo', $matches);
	assertType('array<string>', $matches);
}

function testParseStr() {
	$str="first=value&arr[]=foo+bar&arr[]=baz";
	parse_str($str, $output);

	/*
	echo $output['first'];//value
	echo $output['arr'][0];//foo bar
	echo $output['arr'][1];//baz
	*/

	\PHPStan\Testing\assertType('array<int|string, list<string>|string>', $output);
}

function fooSimilar() {
	$similar = similar_text('foo', 'bar', $percent);
	assertType('int', $similar);
	assertType('float', $percent);
}

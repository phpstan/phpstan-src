<?php declare(strict_types = 1);

namespace Bug15461;

use function PHPStan\Testing\assertType;

/**
 * @param mixed[] $export
 * @return mixed[]
 */
function f(array $export, string $key): array {
	assert(isset($export[$key]));
	assert(is_array($export[$key]));
	unset($export[$key]['x']);
	assertType("array<mixed~'x', mixed>", $export[$key]);
	return $export[$key];
}

/**
 * @param mixed[] $export
 * @return mixed[]
 */
function g(array $export, string $key): array {
	assert(isset($export[$key]));
	assert(is_array($export[$key]));
	unset($export[$key]['x']);
	unset($export[$key]['y']);
	assertType("array<mixed~('x'|'y'), mixed>", $export[$key]);
	return $export;
}

/** @param mixed[] $export */
function nested(array $export, string $key, string $key2): void {
	assert(is_array($export[$key]));
	assert(is_array($export[$key][$key2]));
	unset($export[$key][$key2]['x']);
	assertType("array<mixed~'x', mixed>", $export[$key][$key2]);
	assertType('array<mixed, mixed>', $export[$key]);
}

/** @param mixed[] $export */
function dimUnset(array $export, string $key, string $key2): void {
	assert(is_array($export[$key]));
	unset($export[$key][$key2]);
	assertType('array<mixed, mixed>', $export[$key]);
}

class Foo
{
	/** @var mixed[] */
	public array $prop = [];

	/** @var mixed[] */
	public static array $staticProp = [];

	public function doFoo(string $key): void
	{
		assert(is_array($this->prop[$key]));
		unset($this->prop[$key]['x']);
		assertType("array<mixed~'x', mixed>", $this->prop[$key]);

		assert(is_array(self::$staticProp[$key]));
		unset(self::$staticProp[$key]['x']);
		assertType("array<mixed~'x', mixed>", self::$staticProp[$key]);
	}
}

/** @param array<string, array<string, int>> $export */
function untracked(array $export, string $key): void {
	unset($export[$key]['x']);
	assertType('array<string, array<string, int>>', $export);
	assertType('array<string, int>', $export[$key]);
}

/** @param array{a?: array{b: int, c: int}} $x */
function optionalIntermediateKey(array $x): void {
	unset($x['a']['b']);
	assertType('array{}|array{a: array{c: int}}', $x);
}

/** @param array{a: array{b?: array<int, string>, c: int}} $x */
function optionalKeyOnTrackedLink(array $x, string $key, int $j): void {
	$y = [$key => $x['a']];
	assert(isset($y[$key]));
	unset($y[$key]['b'][$j]);
	assertType('array{b?: array<int, string>, c: int}', $y[$key]);
}

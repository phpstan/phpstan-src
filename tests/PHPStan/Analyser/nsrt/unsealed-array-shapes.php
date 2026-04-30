<?php

namespace UnsealedArrayShapes;

use DateTimeImmutable;
use stdClass;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{a: int, ...} $a
	 * @param array{a: int, ...<string, float>} $b
	 * @param array{a: int, ...<array-key, float>} $c
	 * @param list{int, string, ...<float>} $d
	 * @param list{int, string, 2?: string, 3?: string, ...<float>} $e
	 * @param list{int, string, ...} $f
	 * @param list{int, string, 2?: string, 3?: string, ...} $g
	 */
	public function doFoo(array $a, array $b, array $c, array $d, array $e, array $f, array $g): void
	{
		assertType('array{a: int, ...}', $a);
		foreach ($a as $k => $v) {
			assertType('(int|string)', $k);
			assertType('mixed', $v);
		}

		assertType('array{a: int, ...<string, float>}', $b);
		foreach ($b as $k => $v) {
			assertType('string', $k);
			assertType('float|int', $v);
		}
		assertType('array{a: int, ...<float>}', $c);
		foreach ($c as $k => $v) {
			assertType('(int|string)', $k);
			assertType('float|int', $v);
		}

		assertType('array{int, string, ...<float>}', $d);
		foreach ($d as $k => $v) {
			assertType('int<0, max>', $k);
			assertType('float|int|string', $v);
		}

		assertType('list{0: int, 1: string, 2?: string, 3?: string, ...<float>}', $e);
		foreach ($e as $k => $v) {
			assertType('int<0, max>', $k);
			assertType('float|int|string', $v);
		}

		assertType('array{int, string, ...}', $f);
		foreach ($f as $k => $v) {
			assertType('int<0, max>', $k);
			assertType('mixed', $v);
		}

		assertType('list{0: int, 1: string, 2?: string, 3?: string, ...}', $g);
		foreach ($e as $k => $v) {
			assertType('int<0, max>', $k);
			assertType('float|int|string', $v);
		}
	}

	/**
	 * @param array{a: int, ...<DateTimeImmutable, self>} $a
	 * @return void
	 */
	public function wrongKeyButResolvedToIntString(array $a): void
	{
		assertType('array{a: int, ...<int|string, UnsealedArrayShapes\Foo>}', $a);
	}

	/**
	 * @param array{...<string, self>} $a
	 * @param array{a: int, ...<'b'|'c', string>} $b
	 * @param array{a: int, b: float, ...<'b'|'c', string>} $c
	 */
	public function edgeCases(array $a, array $b, array $c): void
	{
		assertType('array<string, UnsealedArrayShapes\Foo>', $a);
		assertType('array{a: int, b?: string, c?: string}', $b);
		assertType('array{a: int, b: float|string, c?: string}', $c);
	}

	/**
	 * @param array<int, string> $a
	 * @param array<string, string> $b
	 * @param array<string, string> $c
	 * @return void
	 */
	public function generalArray(array $a, array $b, array $c): void
	{
		$a[1] = 'foo';
		assertType("non-empty-array<int, string>&hasOffsetValue(1, 'foo')", $a);

		$b[1] = 'foo';
		assertType("non-empty-array<1|string, string>&hasOffsetValue(1, 'foo')", $b);

		$c['foo'] = 1;
		assertType("non-empty-array<string, 1|string>&hasOffsetValue('foo', 1)", $c);
	}

	public function sealedBecomesUnsealed(string $s, int $i): void
	{
		$a = [];
		$a[] = 5;
		assertType('array{5}', $a);
		$a[$s] = 6;
		assertType('array{5, ...<string, 6>}', $a);
		$a[$i] = 7;
		assertType('array{5|7, ...<int<min, -1>|int<1, max>|string, 6|7>}', $a);

		$b = [];
		$b[$s] = 1;
		assertType('non-empty-array<string, 1>', $b);

		$b[$i] = 2;
		assertType('non-empty-array<int|string, 1|2>', $b);

		$c = [
			1 => 'foo',
			$s => 'bar',
		];
		assertType("array{1: 'foo', ...<string, 'bar'>}", $c);

		$d = [
			$s => 'foo',
			1 => 'bar',
		];
		assertType("array{1: 'bar', ...<string, 'foo'>}", $d);

		$e = [
			$s => 'foo',
		];
		assertType('non-empty-array<string, \'foo\'>', $e);
	}

	/**
	 * Loop iteration's `generalizeType` previously widened the integer key
	 * of a constant array shape to `int<0, max>` whenever the prev/current
	 * iterations had different (but finite) key sets. With the fix that
	 * keeps the constant-array key union when both shapes are sealed,
	 * loop-bounded counters stay within their actual range.
	 */
	public function loopBoundedCounter(): void
	{
		$arr = [];
		for ($i = 0; $i < 5; $i++) {
			$arr[$i] = 'v';
		}
		assertType("non-empty-array<int<0, 4>, 'v'>", $arr);
	}

	public function loopBoundedCounterWithCondition(): void
	{
		$arr = [];
		for ($i = 0; $i < 5; $i++) {
			if (rand()) {
				$arr[$i] = 'v';
			}
		}
		assertType("array<int<0, 4>, 'v'>", $arr);
	}

	/**
	 * The existing `'x'` key keeps its sealed slot through all iterations
	 * while the int counter grows; generalize merges the two sealed shapes
	 * via key union (no widening to `int<0, max>`).
	 */
	public function loopWithExistingSealedKey(): void
	{
		$arr = ['x' => 0];
		for ($i = 0; $i < 5; $i++) {
			$arr[$i] = $i;
		}
		assertType("non-empty-array<'x'|int<0, 4>, int<0, max>>", $arr);
	}

	/**
	 * Each iteration the body assigns a sealed constant key, then a
	 * non-constant offset — that second assignment promotes the array
	 * from sealed to unsealed (folding the unknown offset/value into the
	 * unsealed extras). The iteration's converged shape stays bounded by
	 * the loop's cond instead of widening to `int<0, max>`.
	 */
	public function loopSealedBecomesUnsealedEachIteration(string $s): void
	{
		$arr = [];
		for ($i = 0; $i < 3; $i++) {
			$arr[$i] = 'sealed';
			$arr[$s . '_' . $i] = 'unsealed';
		}
		assertType("non-empty-array<int<0, 2>|non-falsy-string, literal-string&lowercase-string&non-falsy-string>", $arr);
	}

	/**
	 * Starting from a PHPDoc-declared unsealed shape, a loop adds further
	 * non-constant entries. The sealed prefix (`a`) survives, the existing
	 * unsealed extras get unioned with the loop's per-iteration extras.
	 */
	public function loopMergesUnsealedExtras(string $key): void
	{
		/** @var array{a: int, ...<string, int>} $arr */
		$arr = ['a' => 1];
		for ($i = 0; $i < 3; $i++) {
			$arr[$key . $i] = $i;
		}
		assertType("array{a: int, ...<string, int>}", $arr);
	}

	/**
	 * Joining two unsealed shapes with disjoint sealed prefixes via
	 * scope merging collapses the result to a general array of
	 * `string => int` — neither sealed prefix survives because each is
	 * optional from the other branch's perspective and the unsealed
	 * extras of both sides cover the same key/value space.
	 *
	 * @param array{a: int, ...<string, int>} $u1
	 * @param array{b: int, ...<string, int>} $u2
	 */
	public function twoUnsealedJoined(array $u1, array $u2, bool $cond): void
	{
		if ($cond) {
			$arr = $u1;
		} else {
			$arr = $u2;
		}
		assertType("non-empty-array<string, int>", $arr);
	}

	/**
	 * `array_search` on a constant array shape with unsealed extras must
	 * also consider the extras: a strict needle that matches the unsealed
	 * value type makes the unsealed key type a possible result. The
	 * extras are always uncertain (zero or more entries) so `false` stays
	 * a possible result even when an explicit value definitely matches.
	 *
	 * @param array{a: 'foo', b: 'bar', ...<string, 'baz'>} $arr
	 */
	public function searchUnsealedExclusiveValue(array $arr): void
	{
		assertType("'a'", array_search('foo', $arr, true));
		assertType("'b'", array_search('bar', $arr, true));
		assertType("string|false", array_search('baz', $arr, true));
		assertType("false", array_search('quux', $arr, true));
	}

	/**
	 * Strict search: when the unsealed value type is a different type
	 * than any explicit value, only one side can match a given needle.
	 *
	 * @param array{a: int, b: string, ...<int, bool>} $arr
	 */
	public function searchUnsealedStrictTypes(array $arr): void
	{
		assertType("int|false", array_search(true, $arr, true));
		assertType("'a'|false", array_search(42, $arr, true));
		assertType("'b'|false", array_search('hi', $arr, true));
	}

	/**
	 * Both explicit values and the unsealed extras can match a generic
	 * `int` needle. The explicit string keys `'a'`/`'b'` simplify into
	 * the broader `string` from the unsealed extras' key type, so the
	 * union collapses to `string|false`.
	 *
	 * @param array{a: int, b: int, ...<string, int>} $arr
	 */
	public function searchUnsealedNeedleInBothSides(array $arr): void
	{
		assertType("string|false", array_search(99, $arr, true));
	}

	/**
	 * Non-strict search skips the value-type filter — the unsealed
	 * extras are always considered, since loose comparison can succeed
	 * across many otherwise-mismatched value pairs.
	 *
	 * @param array{a: 1, b: 2, ...<string, int>} $arr
	 */
	public function searchUnsealedNonStrict(array $arr): void
	{
		// `'a'` is a definite hit (constant value matches needle exactly,
		// not optional) so `false` is excluded; the explicit-key match
		// then merges into the unsealed-extras' broader `string` key.
		assertType("string", array_search(1, $arr, false));
		assertType("string|false", array_search(99, $arr, false));
	}

	/**
	 * Sealed array shape: searchArray's unsealed branch is a no-op
	 * (the `[NEVER, NEVER]` extras marker is excluded). Only the
	 * explicit keys are considered.
	 */
	public function searchSealed(): void
	{
		$arr = ['a' => 'foo', 'b' => 'bar'];
		assertType("'a'", array_search('foo', $arr, true));
		assertType("false", array_search('baz', $arr, true));
	}

}

class Generics
{

	/**
	 * @template T
	 * @param T $a
	 * @return array{a: int, ...<int, T>}
	 */
	public function replace($a): array
	{

	}

	/**
	 * @template T
	 * @param array{a: int, ...<int, T>} $a
	 * @return T
	 */
	public function infer(array $a)
	{

	}

}

/**
 * @param Generics $g
 * @param array{a: 1, b: 2, ...<int, stdClass>} $a
 * @param array{a: 1, b: 2, ...<string, stdClass>} $b
 * @param array<int, stdClass> $c
 * @param array<string, stdClass> $d
 * @return void
 */
function doFoo(Generics $g, array $a, array $b, array $c, array $d): void {
	assertType('array{a: int, ...<int, stdClass>}', $g->replace(new stdClass()));
	assertType('1|2|3', $g->infer([1, 2, 3, 'a' => 4]));
	assertType('stdClass', $g->infer($a));
	assertType('*NEVER*', $g->infer($b));
	assertType('stdClass', $g->infer($c));
	assertType('stdClass', $g->infer($d));
};

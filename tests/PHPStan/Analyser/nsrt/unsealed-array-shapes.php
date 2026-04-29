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
		assertType('array{5|7, ...<int<min,-1>|int<1,max>|string, 6|7>}', $a);

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

<?php

namespace UnsealedArrayShapes;

use DateTimeImmutable;
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
		assertType('array{...<string, UnsealedArrayShapes\Foo>}', $a);
		assertType('array{a: int, b?: string, c?: string}', $b);
		assertType('array{a: int, b: float|string, c?: string}', $c);
	}

}

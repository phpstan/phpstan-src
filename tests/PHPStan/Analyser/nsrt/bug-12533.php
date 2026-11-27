<?php declare(strict_types = 1);

namespace Bug12533;

use function PHPStan\Testing\assertType;


/**
 * @template T1 of array
 * @template T2 of array
 * @param array<T1> $a1
 * @param array<T2> $a2
 *
 * @return array<T1|T2>
 */
function all(array $a1, array $a2): array {
	return [];
}

/**
 * @template T1 of array
 * @template T2 of array
 * @param array<T1> $a1
 * @param array<T2> $a2
 *
 * @return array<T2|T1>
 */
function allRev(array $a1, array $a2): array {
	return [];
}

/**
 * @template T1 of array
 * @template T2
 * @param array<T1> $a1
 * @param array<T2> $a2
 *
 * @return array<T1|T2>
 */
function first(array $a1, array $a2): array {
	return [];
}

/**
 * @template T1
 * @template T2 of array
 * @param array<T1> $a1
 * @param array<T2> $a2
 *
 * @return array<T1|T2>
 */
function second(array $a1, array $a2): array {
	return [];
}

/**
 * @template T1
 * @template T2
 * @param array<T1> $a1
 * @param array<T2> $a2
 *
 * @return array<T1|T2>
 */
function none(array $a1, array $a2): array {
	return [];
}

/**
 * @param array<array{foo: string}> $a1
 * @param array<array{bar: int}> $a2
 */
function mergeCheck(array $a1, array $a2): void {
	assertType('array<array{bar: int}|array{foo: string}>', all($a1, $a2)); // Should be array<array{foo: string}| array{bar: int}>
	assertType('array<array{bar: int}|array{foo: string}>', allRev($a1, $a2)); // Should be array<array{foo: string}| array{bar: int}>
	assertType('array<array{bar: int}|array{foo: string}>', first($a1, $a2));
	assertType('array<array{bar: int}|array{foo: string}>', second($a1, $a2));
	assertType('array<array{bar: int}|array{foo: string}>', none($a1, $a2));
}

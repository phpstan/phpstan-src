<?php // lint >= 8.1

namespace ArrayMapUnpackedArgsFirstClassCallable;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param T $value
 * @return T
 */
function identity($value)
{
	return $value;
}

/**
 * @param list<list<int>> $lists
 * @param array{0: array{1}, 1?: array{2, 3}} $shorterFirst
 */
function genericCallbackWithUnknownCount(array $lists, array $shorterFirst): void
{
	assertType('list<int>', array_map(identity(...), ...$lists));
	assertType('non-empty-list<1|null>', array_map(identity(...), ...$shorterFirst));
}

/**
 * @template A
 * @template B
 * @template C
 * @param A $a
 * @param B $b
 * @param C $c
 * @return array{A, B, C}
 */
function tuple($a, $b, $c): array
{
	return [$a, $b, $c];
}

/**
 * @param array{array{1}, array{2, 3}}|array{array{4, 5}, array{6}} $shapes
 * @param array{array{'a', 'b'}}|array{array{'c', 'd'}}|array{array{'e', 'f'}}|array{array{'g', 'h'}}|array{array{'i', 'j'}}|array{array{'k', 'l'}}|array{array{'m', 'n'}}|array{array{'o', 'p'}}|array{array{'q', 'r'}} $manyShapes
 */
function tooManyListsToMapOneByOne(array $shapes, array $manyShapes): void
{
	// each shape of $shapes has a shorter array, so every value can be null
	assertType("non-empty-list<array{1|4|5|null, 2|3|6|null, 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q'|'r'|null}>", array_map(tuple(...), ...$shapes, ...$manyShapes));
}

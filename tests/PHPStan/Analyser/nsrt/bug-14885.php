<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14885;

use Closure;
use function PHPStan\Testing\assertType;

/**
 * @template Tv
 * @template T
 *
 * @param iterable<Tv> $iterable
 * @param (Closure(Tv): T) $function
 *
 * @return list<T>
 */
function map(iterable $iterable, Closure $function): array
{
	$result = [];
	foreach ($iterable as $value) {
		$result[] = $function($value);
	}

	return $result;
}

function issue(): void
{
	assertType(
		"list<array{'a'|'b'|'c', int<0, 255>}>",
		map(['a', 'b', 'c'], fn ($a) => [$a, ord($a)]),
	);

	assertType(
		"list<array{'a'|'c', 'b'|'d'}>",
		map([['a', 'b'], ['c', 'd']], fn ($x) => [$x[0], $x[1]]),
	);
}

/**
 * @template K of array-key
 * @template T
 * @template L of array-key
 * @template U
 *
 * @param array<K, T> $array
 * @param callable(T, K): iterable<L, U> $fn
 *
 * @return array<L, U>
 */
function mapWithKeys(array $array, callable $fn): array
{
	return [];
}

/**
 * @template T
 * @template U
 *
 * @param callable(T): U $fn
 * @param array<T> $array
 *
 * @return list<U>
 */
function mapCallableFirst(callable $fn, array $array): array
{
	return [];
}

/**
 * @template T
 * @template U
 *
 * @param array<T> $array
 * @param (callable(T): U)|null $fn
 *
 * @return list<U>
 */
function mapNullable(array $array, ?callable $fn): array
{
	return [];
}

/**
 * @template T
 * @template TAcc
 *
 * @param array<T> $array
 * @param callable(TAcc, T): TAcc $fn
 * @param TAcc $initial
 *
 * @return TAcc
 */
function reduce(array $array, callable $fn, mixed $initial): mixed
{
	return $initial;
}

final class Mapper
{

	/**
	 * @template T
	 * @template U
	 *
	 * @param array<T> $array
	 * @param callable(T): U $fn
	 *
	 * @return list<U>
	 */
	public function map(array $array, callable $fn): array
	{
		return [];
	}

	/**
	 * @template T
	 * @template U
	 *
	 * @param array<T> $array
	 * @param callable(T): U $fn
	 *
	 * @return list<U>
	 */
	public static function staticMap(array $array, callable $fn): array
	{
		return [];
	}

}

/**
 * @template T
 * @template U
 */
final class Mapped
{

	/**
	 * @param array<T> $array
	 * @param callable(T): U $fn
	 */
	public function __construct(public array $array, public $fn)
	{
	}

	/** @return U */
	public function first(): mixed
	{
		throw new \LogicException();
	}

}

/**
 * @param list<array{id: int, name: string}> $rows
 * @param list<list<array{id: int}>> $nested
 * @param list<\stdClass&object{name: string}> $objects
 */
function natively(array $rows, array $nested, array $objects, Mapper $mapper): void
{
	assertType('array<1, string>', mapWithKeys($rows, static fn (array $row): iterable => yield 1 => $row['name']));
	assertType('array<int, string>', mapWithKeys($rows, static fn (array $row): iterable => yield $row['id'] => $row['name']));
	assertType('array<int, string>', mapWithKeys($rows, static function (array $row): iterable {
		$id = $row['id'];
		yield $id => $row['name'];
	}));
	assertType('list<string>', map($objects, static fn (object $o) => $o->name));

	assertType('list<string>', mapCallableFirst(static fn (array $row) => $row['name'], $rows));
	assertType('list<string>', mapNullable($rows, static fn (array $row) => $row['name']));
	assertType('list<string>', map(function: static fn (array $row) => $row['name'], iterable: $rows));
	assertType('list<list<int>>', map($nested, static fn (array $list) => map($list, static fn (array $row) => $row['id'])));
	assertType('int', reduce($rows, static fn (int $carry, array $row) => $carry + $row['id'], 0));

	assertType('list<string>', $mapper->map($rows, static fn (array $row) => $row['name']));
	assertType('list<string>', Mapper::staticMap($rows, static fn (array $row) => $row['name']));
	assertType('string', (new Mapped($rows, static fn (array $row) => $row['name']))->first());
}

/**
 * @template X of array{id: int}
 * @param list<X> $rows
 */
function callerTemplate(array $rows): void
{
	assertType('list<int>', map($rows, static fn (array $row) => $row['id']));
}

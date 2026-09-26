<?php // lint >= 8.1

declare(strict_types = 1);

namespace NarrowedSubjectInferredLiteral;

use function PHPStan\Testing\assertType;

class User
{

}

enum Digit: int
{

	case One = 1;

}

/**
 * @template TKey of array-key
 * @template TValue
 */
class Collection
{

	/**
	 * @template TNewKey of array-key|\UnitEnum
	 *
	 * @param  (callable(TValue, TKey): TNewKey)|array<mixed>|string  $keyBy
	 * @return static<($keyBy is (array|string) ? array-key : (TNewKey is \UnitEnum ? array-key : TNewKey)), TValue>
	 */
	public function keyBy($keyBy)
	{
		throw new \Exception();
	}

	/**
	 * @template TNewKey of array-key|\UnitEnum
	 *
	 * @param  callable(TValue, TKey): TNewKey  $keyBy
	 * @return static<(TNewKey is \UnitEnum ? array-key : TNewKey), TValue>
	 */
	public function keyByCallback($keyBy)
	{
		throw new \Exception();
	}

}

/**
 * @template T of array-key
 * @template U of array-key
 * @param T $a
 * @param U $b
 * @return Collection<(T is U ? T : int), int>
 */
function templateTarget($a, $b)
{
	throw new \Exception();
}

/**
 * @template T of array-key
 * @template U of array-key
 * @param T $a
 * @param U $b
 * @return Collection<(T is not U ? int : T), int>
 */
function negatedTemplateTarget($a, $b)
{
	throw new \Exception();
}

/** @param Collection<int, User> $collection */
function test(Collection $collection): void
{
	assertType("NarrowedSubjectInferredLiteral\\Collection<'foo', NarrowedSubjectInferredLiteral\\User>", $collection->keyBy(fn ($user) => 'foo'));
	assertType('NarrowedSubjectInferredLiteral\\Collection<0, NarrowedSubjectInferredLiteral\\User>', $collection->keyBy(static fn ($user): int => 0));
	assertType('NarrowedSubjectInferredLiteral\\Collection<(int|string), NarrowedSubjectInferredLiteral\\User>', $collection->keyBy('name'));
	assertType('NarrowedSubjectInferredLiteral\\Collection<(int|string), NarrowedSubjectInferredLiteral\\User>', $collection->keyBy(static fn ($user) => Digit::One));

	assertType("NarrowedSubjectInferredLiteral\\Collection<'foo', NarrowedSubjectInferredLiteral\\User>", $collection->keyByCallback(fn ($user) => 'foo'));
	assertType('NarrowedSubjectInferredLiteral\\Collection<(int|string), NarrowedSubjectInferredLiteral\\User>', $collection->keyByCallback(static fn ($user) => Digit::One));

	// the condition compares the generalized T and U, the branch keeps the inferred T
	assertType("NarrowedSubjectInferredLiteral\\Collection<'foo', int>", templateTarget('foo', 'bar'));
	assertType("NarrowedSubjectInferredLiteral\\Collection<'foo', int>", negatedTemplateTarget('foo', 'bar'));
}

/**
 * @template TKey of array-key
 * @template TValue
 */
interface Enumerable
{

	/**
	 * @template TNewKey of array-key|\UnitEnum
	 *
	 * @param  (callable(TValue, TKey): TNewKey)|array<mixed>|string  $keyBy
	 * @return static<($keyBy is (array|string) ? array-key : (TNewKey is \UnitEnum ? array-key : TNewKey)), TValue>
	 */
	public function keyBy($keyBy);

}

/**
 * @template TKey of array-key
 * @template TValue
 * @implements Enumerable<TKey, TValue>
 */
class InheritedCollection implements Enumerable
{

	/**
	 * {@inheritDoc}
	 */
	public function keyBy($keyBy)
	{
		throw new \Exception();
	}

}

/** @param InheritedCollection<int, User> $collection */
function testInherited(InheritedCollection $collection): void
{
	assertType("NarrowedSubjectInferredLiteral\\InheritedCollection<'foo', NarrowedSubjectInferredLiteral\\User>", $collection->keyBy(function ($user, $int) {
		return 'foo';
	}));
	assertType('NarrowedSubjectInferredLiteral\\InheritedCollection<0, NarrowedSubjectInferredLiteral\\User>', $collection->keyBy(static fn ($user): int => 0));
	assertType('NarrowedSubjectInferredLiteral\\InheritedCollection<(int|string), NarrowedSubjectInferredLiteral\\User>', $collection->keyBy('name'));
	assertType('NarrowedSubjectInferredLiteral\\InheritedCollection<(int|string), NarrowedSubjectInferredLiteral\\User>', $collection->keyBy(static fn ($user) => Digit::One));
}

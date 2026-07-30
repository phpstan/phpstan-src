<?php // lint >= 8.0

namespace Bug15027;

use function PHPStan\Testing\assertType;

/**
 * @template Value of string|list<string>
 */
final class LanguageProperty
{
	/** @var Value */
	public $value;

	/**
	 * @param Value   $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * @template T of string|list<string>
	 * @param T $value
	 * @return self<T>
	 */
	public static function create($value): self
	{
		return new self($value);
	}
}

/**
 * @template T
 */
final class Unbounded
{
	/** @param T $value */
	public function __construct(public $value)
	{
	}
}

/**
 * @template T of array
 */
final class BoundToArray
{
	/** @param T $value */
	public function __construct(public $value)
	{
	}
}

/**
 * @template T of array<string, mixed>
 */
final class BoundToMixedMap
{
	/** @param T $value */
	public function __construct(public $value)
	{
	}
}

/**
 * @template T of array<string, string>
 */
final class BoundToStringMap
{
	/** @param T $value */
	public function __construct(public $value)
	{
	}
}

/**
 * @template T of list<string>
 */
final class BoundToList
{
	/** @param T $value */
	public function __construct(public $value)
	{
	}
}

/**
 * @template T of string|list<list<string>>
 */
final class BoundToNestedList
{
	/** @param T $value */
	public function __construct(public $value)
	{
	}
}

/**
 * @template T of iterable<int, string>
 */
final class BoundToIterable
{
	/** @param T $value */
	public function __construct(public $value)
	{
	}
}

/**
 * @template T of int|list<int>
 */
final class BoundToIntOrList
{
	/** @param T $value */
	public function __construct(public $value)
	{
	}
}

/**
 * @template T of \DateTimeInterface|string
 */
final class BoundToObjectOrString
{
	/** @param T $value */
	public function __construct(public $value)
	{
	}
}

/**
 * @template T of \Traversable
 */
final class BoundToTraversable
{
	/** @param T $value */
	public function __construct(public $value)
	{
	}
}

function doFoo(): string
{
	return 'hallo';
}

/**
 * @template T of string|list<string>
 * @param T $value
 * @return LanguageProperty<T>
 */
function createLanguageProperty($value): LanguageProperty
{
	return new LanguageProperty($value);
}

/**
 * @param non-empty-list<string> $nonEmptyList
 * @param numeric-string $numericString
 * @param int<1, 5> $intRange
 * @param array<string, string> $stringMap
 */
function test(
	string $s,
	array $nonEmptyList,
	string $numericString,
	int $intRange,
	array $stringMap,
	\DateTimeImmutable $date,
	\ArrayIterator $iterator
): void
{
	assertType('Bug15027\LanguageProperty<list<string>>', new LanguageProperty(['abc']));
	assertType('Bug15027\LanguageProperty<list<string>>', new LanguageProperty([$s]));
	assertType('Bug15027\LanguageProperty<list<string>>', new LanguageProperty($nonEmptyList));
	assertType('Bug15027\LanguageProperty<list<string>>', new LanguageProperty([]));
	assertType('Bug15027\LanguageProperty<string>', new LanguageProperty('abc'));
	assertType('Bug15027\LanguageProperty<string>', new LanguageProperty('abc' . doFoo()));
	assertType('Bug15027\LanguageProperty<string>', new LanguageProperty($numericString));

	// the same widening applies when the generic type is created by a factory
	assertType('Bug15027\LanguageProperty<list<string>>', LanguageProperty::create(['abc']));
	assertType('Bug15027\LanguageProperty<string>', LanguageProperty::create('abc' . doFoo()));
	assertType('Bug15027\LanguageProperty<list<string>>', createLanguageProperty(['abc']));
	assertType('Bug15027\LanguageProperty<string>', createLanguageProperty('abc' . doFoo()));

	assertType('Bug15027\BoundToList<list<string>>', new BoundToList(['abc']));
	assertType('Bug15027\BoundToNestedList<list<list<string>>>', new BoundToNestedList([['abc']]));
	assertType('Bug15027\BoundToIterable<iterable<int, string>>', new BoundToIterable(['abc']));
	assertType('Bug15027\BoundToIntOrList<int>', new BoundToIntOrList($intRange));
	assertType('Bug15027\BoundToIntOrList<list<int>>', new BoundToIntOrList([1, 2]));
	assertType('Bug15027\BoundToStringMap<array<string, string>>', new BoundToStringMap(['a' => 'b']));
	assertType('Bug15027\BoundToStringMap<array<string, string>>', new BoundToStringMap($stringMap));

	// bounds that say more than the argument does keep the precise type
	assertType('Bug15027\Unbounded<array{string}>', new Unbounded(['abc']));
	assertType('Bug15027\Unbounded<string>', new Unbounded('abc'));
	assertType('Bug15027\BoundToArray<array{string}>', new BoundToArray(['abc']));
	assertType('Bug15027\BoundToMixedMap<array{foo: string, bar: int}>', new BoundToMixedMap(['foo' => 'abc', 'bar' => 1]));

	// class precision is not lost
	assertType('Bug15027\BoundToObjectOrString<DateTimeImmutable>', new BoundToObjectOrString($date));
	assertType('Bug15027\BoundToObjectOrString<string>', new BoundToObjectOrString('abc' . doFoo()));
	assertType('Bug15027\BoundToTraversable<ArrayIterator>', new BoundToTraversable($iterator));
}

<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15798;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @phpstan-consistent-constructor
 */
class Box
{
	/** @param T $value */
	public function __construct(private mixed $value) {}

	/** @return T */
	public function get(): mixed
	{
		return $this->value;
	}

	/**
	 * @template U
	 * @param U $value
	 * @return static<U>
	 */
	public function with(mixed $value): static
	{
		return new static($value);
	}

	/** @return static<T> */
	public function copy(): static
	{
		return new static($this->value);
	}

	/** @return static<int> */
	public function withInt(): static
	{
		return new static(1);
	}
}

/** @extends Box<string> */
final class StringBox extends Box {}

/**
 * @template V
 * @extends Box<V>
 */
class GenericBox extends Box {}

/**
 * @template V of string
 * @extends Box<V>
 */
class BoundedBox extends Box {}

/**
 * @template K
 * @template V
 * @phpstan-consistent-constructor
 */
class Map
{
	/**
	 * @param K $key
	 * @param V $value
	 */
	public function __construct(public mixed $key, public mixed $value) {}

	/**
	 * @template K2
	 * @template V2
	 * @param K2 $key
	 * @param V2 $value
	 * @return static<K2, V2>
	 */
	public function with(mixed $key, mixed $value): static
	{
		return new static($key, $value);
	}

	/**
	 * @template V2
	 * @param V2 $value
	 * @return static<K, V2>
	 */
	public function withValue(mixed $value): static
	{
		return new static($this->key, $value);
	}
}

/**
 * @template V
 * @extends Map<int, V>
 */
class IntKeyMap extends Map {}

function (StringBox $box, GenericBox $genericBox, BoundedBox $boundedBox, IntKeyMap $map, string $s, int $i): void {
	$box = new StringBox('before');
	$changed = $box->with(42);
	assertType('Bug15798\Box<42>', $changed);
	assertType('42', $changed->get());

	assertType('Bug15798\StringBox', $box->with('foo'));
	assertType('Bug15798\StringBox', $box->with($s));
	assertType('Bug15798\Box<int>|Bug15798\StringBox', $box->with(rand() ? $s : $i));
	assertType('Bug15798\StringBox', $box->copy());
	assertType('Bug15798\Box<int>', $box->withInt());

	assertType('Bug15798\GenericBox<42>', $genericBox->with(42));
	assertType('Bug15798\BoundedBox<string>', $boundedBox->with($s));
	assertType('Bug15798\Box<42>', $boundedBox->with(42));
	assertType('Bug15798\Box<int>', $boundedBox->withInt());

	assertType("Bug15798\\IntKeyMap<'foo'>", $map->with(1, 'foo'));
	assertType("Bug15798\\Map<'foo', 'bar'>", $map->with('foo', 'bar'));
	assertType("Bug15798\\IntKeyMap<'foo'>", $map->withValue('foo'));
};

/** @extends Box<string> */
class NonFinalStringBox extends Box
{
	public function doFoo(): void
	{
		assertType('Bug15798\Box<42>', $this->with(42));
		assertType('static(Bug15798\NonFinalStringBox)', $this->with('foo'));
		assertType('static(Bug15798\Box<int>)', static::withInt());
	}
}

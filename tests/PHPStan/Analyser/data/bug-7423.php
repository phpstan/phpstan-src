<?php
declare(strict_types=1);

namespace Bug7423;

use Iterator;
use IteratorAggregate;
use function array_key_exists;
use function count;
use function PHPStan\Testing\assertType;

/**
 * @template T
 */
interface TypeGenerator
{
	/**
	 * @return T
	 */
	public function __invoke();
}

/**
 * @implements TypeGenerator<int>
 */
final class IntType implements TypeGenerator
{
	public function __invoke(): int
	{
		return mt_rand(0, 9);
	}
}

/**
 * @implements TypeGenerator<string>
 */
final class StringType implements TypeGenerator
{
	public function __invoke(): string
	{
		return chr(random_int(33, 126));
	}
}


/**
 * @implements TypeGenerator<null>
 */
final class NullType implements TypeGenerator
{
	public function __invoke()
	{
		return null;
	}
}

/**
 * @template TKey of array-key
 * @template T
 *
 * @implements TypeGenerator<array<TKey, T>>
 */
final class ArrayType implements TypeGenerator
{
	/**
	 * @var list<TypeGenerator<TKey>>
	 */
	private array $keys = [];

	/**
	 * @var list<TypeGenerator<T>>
	 */
	private array $values = [];

	/**
	 * @param TypeGenerator<TKey> $key
	 * @param TypeGenerator<T> $value
	 */
	public function __construct(TypeGenerator $key, TypeGenerator $value)
	{
		$this->keys[] = $key;
		$this->values[] = $value;
	}

	/**
	 * @return array<TKey, T>
	 */
	public function __invoke(): array
	{
		$keys = $values = [];
		$countKeys = count($this->keys);

		for ($i = 0; count($keys) < $countKeys; ++$i) {
			$key = ($this->keys[$i])();

			if (array_key_exists($key, $keys)) {
				--$i;

				continue;
			}

			$keys[$key] = $key;
		}

		foreach ($this->values as $value) {
			$values[] = ($value)();
		}

		return array_combine($keys, $values);
	}

	/**
	 * @template VKey of array-key
	 * @template V
	 *
	 * @param TypeGenerator<VKey> $key
	 * @param TypeGenerator<V> $value
	 *
	 * @return ArrayType<TKey|VKey, T|V>
	 */
	public function add(TypeGenerator $key, TypeGenerator $value): ArrayType
	{
		// @TODO: See if we can fix this issue in PHPStan/PSalm.
		// There should not be @var annotation here.
		// An issue has been opened: https://github.com/vimeo/psalm/issues/8066
		/** @var ArrayType<TKey|VKey, T|V> $clone */
		$clone = clone $this;

		/** @var list<TypeGenerator<TKey|VKey>> $keys */
		$keys = array_merge($this->keys, [$key]);
		$clone->keys = $keys;

		/** @var list<TypeGenerator<T|V>> $values */
		$values = array_merge($this->values, [$value]);
		$clone->values = $values;

		return $clone;
	}
}

(function () {
	$sub1 = new ArrayType(new StringType(), new NullType());
	assertType('array<string, null>', $sub1()); // Passing

	$sub2 = new ArrayType(new IntType(), new NullType());
	assertType('array<int, null>', $sub2()); // Passing

	$sub3 = (new ArrayType(new StringType(), new StringType()))->add(new IntType(), new IntType());
	assertType('array<int|string, int|string>', $sub3()); // Failing
})();

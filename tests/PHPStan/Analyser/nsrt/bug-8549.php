<?php // lint >= 8.0

namespace Bug8549Types;

use function PHPStan\Testing\assertType;

/**
 * @template TArray of array
 * @extends \ArrayAccess<key-of<TArray>, value-of<TArray>>
 */
interface Options extends \ArrayAccess
{

	/**
	 * @param key-of<TArray> $offset
	 */
	public function offsetExists(mixed $offset): bool;

	/**
	 * @template TOffset of key-of<TArray>
	 * @param TOffset $offset
	 * @return TArray[TOffset]
	 */
	public function offsetGet(mixed $offset);

	/**
	 * @template TOffset of key-of<TArray>
	 * @param TOffset $offset
	 * @param TArray[TOffset] $value
	 */
	public function offsetSet(mixed $offset, mixed $value): void;

	/**
	 * @template TOffset of key-of<TArray>
	 * @param TOffset $offset
	 */
	public function offsetUnset(mixed $offset): void;

}

class Foo
{

	/**
	 * @param Options<array{name: string, age: int}> $options
	 */
	public function read(Options $options): void
	{
		assertType('string', $options['name']);
		assertType('int', $options['age']);
		assertType('int', $options['age'] ??= 5);
		assertType('string', $options['name'] ??= 'x');
		assertType('int', $options['age'] += 1);
		assertType('non-falsy-string', $options['name'] .= 'x');
	}

	/**
	 * @param Options<array{name: string, age: int}> $options
	 * @param 'name'|'age' $key
	 */
	public function unionKey(Options $options, string $key): void
	{
		assertType('int|string', $options[$key]);
	}

}

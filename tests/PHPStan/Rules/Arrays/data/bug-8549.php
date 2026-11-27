<?php // lint >= 8.0

namespace Bug8549;

// @link https://github.com/symfony/symfony/blob/6.2/src/Symfony/Component/OptionsResolver/Options.php

/**
 * @template TArray of array
 * @extends \ArrayAccess<key-of<TArray>, value-of<TArray>>
 */
interface Options extends \ArrayAccess {

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

class HelloWorld
{
	/**
	 * @param Options<array{name: string}> $options
	 */
	public function sayHello(Options $options): void
	{
		\PHPStan\dumpType($options['name']);
		$options['name'] = "foo";
		$options['name'] = 1;
	}
}

class MoreCases
{

	/**
	 * @param Options<array{name: string, age: int}> $options
	 */
	public function shape(Options $options): void
	{
		$options['name'] = 'foo';
		$options['age'] = 1;
		$options['age'] = 'foo';
		$options['nope'] = 'foo';
		$options[] = 'foo';
	}

	/**
	 * @param Options<array<string, mixed>> $options
	 */
	public function map(Options $options, mixed $value): void
	{
		$options['context'] = 'edit';
		$options['_fields'] = $value;
		$options[1] = 'edit';
	}

}

class CompoundAssignments
{

	/**
	 * @param Options<array{name: string, age: int}> $options
	 */
	public function compound(Options $options): void
	{
		$options['age'] ??= 5;
		$options['name'] ??= 'x';
		$options['age'] += 1;
		$options['name'] .= 'x';
		$options['age'] .= 'x';
	}

}

class UnionKeys
{

	/**
	 * @param Options<array{name: string, age: int}> $options
	 * @param 'name'|'age' $key
	 * @param 'name'|'nope' $partlyUnknownKey
	 */
	public function union(Options $options, string $key, string $partlyUnknownKey): void
	{
		$options[$key] = 'foo';
		$options[$key] = 1;
		$options[$key] = 1.5;
		$options[$partlyUnknownKey] = 'foo';
	}

}

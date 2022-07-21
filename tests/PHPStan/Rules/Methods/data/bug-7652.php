<?php declare(strict_types = 1);

namespace Bug7652;

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

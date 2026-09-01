<?php declare(strict_types = 1);

namespace Bug12704;

/**
 * @template TValue
 */
final class Foo {
	/**
     * @return static<int>
     */
    public function baz() 
	{
        return new static;
	}
}

/**
 * @template TValue
 */
final class Bar {
	/**
     * @return self<int>
     */
    public function baz() 
	{
        return new self;
	}
}

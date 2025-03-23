<?php declare(strict_types = 1);

namespace Bug12691;

use Closure;
use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class Option
{
	/**
	 * @param T $value
	 */
	public function __construct(
		public mixed $value,
	) {
	}

	/**
	 * @template S
	 * @param callable(T): S $callable
	 * @return self<S>
	 */
	public function map($callable): self
	{
		return new self($callable($this->value));
	}

	/**
	 * @template S
	 * @param Closure(T): S $callable
	 * @return self<S>
	 */
	public function mapClosure($callable): self
	{
		return new self($callable($this->value));
	}
}

/**
 * @param Option<non-empty-array<int>> $ints
 */
function doFoo(Option $ints): void {
	assertType('Bug12691\\Option<non-empty-list<int>>', $ints->map(array_values(...)));
	assertType('Bug12691\\Option<non-empty-list<int>>', $ints->map('array_values'));
	assertType('Bug12691\\Option<non-empty-list<int>>', $ints->map(static fn ($value) => array_values($value)));
};

/**
 * @param Option<non-empty-array<int>> $ints
 */
function doFooClosure(Option $ints): void {
	assertType('Bug12691\\Option<non-empty-list<int>>', $ints->mapClosure(array_values(...)));
	assertType('Bug12691\\Option<non-empty-list<int>>', $ints->mapClosure(static fn ($value) => array_values($value)));
};

/**
 * @template T
 * @param array<T> $a
 * @return ($a is non-empty-array ? non-empty-list<T> : list<T>)
 */
function myArrayValues(array $a): array {

}

/**
 * @param Option<non-empty-array<int>> $ints
 */
function doBar(Option $ints): void {
	assertType('Bug12691\\Option<non-empty-list<int>>', $ints->mapClosure(myArrayValues(...)));
	assertType('Bug12691\\Option<non-empty-list<int>>', $ints->mapClosure(static fn ($value) => myArrayValues($value)));
};

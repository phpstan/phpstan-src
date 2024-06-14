<?php

namespace Bug10254;

use Closure;
use RuntimeException;
use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class Option {
	/**
	 * @param T $value
	 */
	private function __construct(private mixed $value)
	{
	}

	/**
	 * @template Tv
	 * @param Tv $value
	 * @return self<Tv>
	 */
	public static function some($value): self
	{
		return new self($value);
	}

	/**
	 * @template Tu
	 *
	 * @param (Closure(T): Tu) $closure
	 *
	 * @return Option<Tu>
	 */
	public function map(Closure $closure): self
	{
		return new self($closure($this->unwrap()));
	}

	/**
	 * @return T
	 */
	public function unwrap()
	{
		if ($this->value === null) {
			throw new RuntimeException();
		}

		return $this->value;
	}

	/**
	 * @template To
	 * @param self<To> $other
	 * @return self<array{0: T, 1: To}>
	 */
	public function zip(self $other)
	{
		return new self([
			$this->unwrap(),
			$other->unwrap()
		]);
	}
}


function (): void {
	$value = Option::some(1)
		->zip(Option::some(2));

	assertType('Bug10254\\Option<array{int, int}>', $value);

	$value1 = $value->map(function ($value) {
		assertType('int', $value[0]);
		assertType('int', $value[1]);
		return $value[0] + $value[1];
	});

	assertType('Bug10254\\Option<int>', $value1);

	$value2 = $value->map(function ($value): int {
		assertType('int', $value[0]);
		assertType('int', $value[1]);
		return $value[0] + $value[1];
	});

	assertType('Bug10254\\Option<int>', $value2);

};

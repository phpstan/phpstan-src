<?php

namespace Bug7609;

use function PHPStan\Testing\assertType;

/** @template T */
interface P {
	/** @return T */
	public function value(): mixed;
}

/**
 * @template T
 * @implements P<T>
 */
class C implements P {
	/** @param T $v */
	public function __construct(private mixed $v) {}

	/** @return T */
	public function value(): mixed { return $this->v; }
}

/**
 * @param P<int> $o
 */
function f(P $o): int {
	assertType('Bug7609\P<int>', $o);

	if ($o instanceof C) {
		assertType('Bug7609\C<int>', $o);
		return $o->value();
	}

	assertType('Bug7609\P~Bug7609\C<int><int>', $o);

	return 0;
}

<?php declare(strict_types = 1);

namespace Bug9634;

/** @template T */
interface Option {
	/** @return self<never> */
	static function none(): self;

	/** @return T */
	function unwrap(): mixed;

	/**
	 * @return (T is never ? false : bool)
	 */
	function isSome(): bool;
}

/** @param Option<never> $o */
function f(Option $o): true {
	return $o->isSome();
}

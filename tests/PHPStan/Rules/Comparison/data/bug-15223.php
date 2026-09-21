<?php declare(strict_types = 1);

namespace Bug15223;

/** @template TValue */
abstract class Coll
{

	/** @return TValue|null */
	abstract public function first();

	/**
	 * @phpstan-assert-if-true null $this->first()
	 * @phpstan-assert-if-false TValue $this->first()
	 */
	abstract public function isEmpty(): bool;

}

/** @param Coll<string> $c */
function f(Coll $c): void
{
	if ($c->isEmpty()) {
		echo 'empty';
	}
}

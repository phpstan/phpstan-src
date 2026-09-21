<?php declare(strict_types = 1);

namespace Bug15223Static;

/** @template TValue */
abstract class Coll
{

	/** @return TValue|null */
	abstract public function first();

	/**
	 * @param self<TValue> $coll
	 * @phpstan-assert-if-true null $coll->first()
	 * @phpstan-assert-if-false TValue $coll->first()
	 */
	public static function isEmptyOf(self $coll): bool
	{
		throw new \Exception();
	}

}

/** @param Coll<string> $c */
function byName(Coll $c): void
{
	if (Coll::isEmptyOf($c)) {
		echo 'empty';
	}
}

/** @param Coll<string> $c */
function byExpr(Coll $c): void
{
	if ($c::isEmptyOf($c)) {
		echo 'empty';
	}
}

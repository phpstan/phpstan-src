<?php declare(strict_types = 1);

namespace Bug15167Methods;

/** @template-covariant T */
interface P
{

	/**
	 * @template TRejected
	 * @param ?(callable(\Throwable): (P<TRejected>|TRejected)) $onRejected
	 * @return P<T|TRejected>
	 */
	public function catch(?callable $onRejected = null): P;

	/**
	 * @template TR
	 * @param callable(\Throwable): (P<TR>|TR) $onRejected
	 * @return P<T|TR>
	 */
	public function catchNotNullable(callable $onRejected): P;

}

/** @param P<int> $p */
function f(P $p): void
{
	$throwing = static function (\Throwable $e): void {
		throw $e;
	};
	$notThrowing = static function (\Throwable $e): void {
	};

	$p->catch($throwing);
	$p->catchNotNullable($throwing);
	$p->catch($notThrowing);
}

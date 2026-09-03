<?php declare(strict_types = 1);

namespace Bug15167;

use function PHPStan\Testing\assertType;

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

	assertType('static-Closure(Throwable): never', $throwing);
	assertType('static-Closure(Throwable): void', $notThrowing);

	assertType('Bug15167\\P<int>', $p->catch($throwing));

	assertType('Bug15167\\P<int>', $p->catchNotNullable($throwing));

	assertType('Bug15167\\P<int|void>', $p->catch($notThrowing));
}

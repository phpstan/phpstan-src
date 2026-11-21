<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15770;

use Closure;
use RuntimeException;
use function PHPStan\Testing\assertType;

/** @template T */
final class Opt
{

	/**
	 * @template E
	 * @param (Closure(): Opt<E>) $closure
	 * @return Opt<T|E>
	 */
	public function orElse(Closure $closure): Opt
	{
		return $this;
	}

	/**
	 * @template E
	 * @param (callable(): Opt<E>) $callback
	 * @return Opt<T|E>
	 */
	public function orElseCallable(callable $callback): Opt
	{
		return $this;
	}

}

/** @param Opt<int> $o */
function test(Opt $o): void
{
	assertType('Bug15770\\Opt<int>', $o->orElse(static fn () => throw new RuntimeException()));
	assertType('Bug15770\\Opt<int>', $o->orElseCallable(static fn () => throw new RuntimeException()));
}

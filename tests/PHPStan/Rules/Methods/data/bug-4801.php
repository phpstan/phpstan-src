<?php declare(strict_types = 1);

namespace Bug4801;

/**
 * @template T
 */
interface I {
	/**
	 * @template TResult = T
	 * @param (callable(T): TResult)|null $a
	 * @return I<TResult>
	 */
  public function work(callable|null $a): I;
}

/**
 * @param I<string> $i
 */
function x(I $i) {
	$i->work(null);
}

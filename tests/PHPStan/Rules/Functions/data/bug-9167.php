<?php

namespace Bug9167;

use Closure;

class A {
	/**
	 * @template TRet
	 * @param Closure(int):TRet $next
	 * @return TRet
	 */
	public function __invoke(Closure $next) {
		return $next(12);
	}
}

/**
 * @template T
 * @param T $in
 * @return T
 */
function value(mixed $in): mixed {
	return $in;
}

function want_int(int $in): void {}

function pass_b(): void {
	$a = new A();
	want_int($a(value(...)));
}

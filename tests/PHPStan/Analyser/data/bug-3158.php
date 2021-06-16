<?php

namespace Bug3158;


use function PHPStan\Testing\assertType;

/**
 * @template T as object
 * @param class-string<T> $className
 * @param \Closure(T):void $outmaker
 * @return T
 */
function createProxy(
	string $className,
	\Closure $outmaker
) : object {
	$t = new $className();
	$outmaker($t);
	return $t;
}

class AParent {}

class A extends AParent {
	public function bar() : void {}
}

function (): void {
	$proxy = createProxy(A::class, function(AParent $o):void {});
	assertType(A::class, $proxy);
};

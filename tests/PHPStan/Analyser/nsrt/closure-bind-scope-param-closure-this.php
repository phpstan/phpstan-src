<?php declare(strict_types = 1);

namespace ClosureBindScopeParamClosureThis;

use function PHPStan\Testing\assertType;

class Foo
{

	public const A = 'Foo';

}

/**
 * @param-closure-this Foo $callback
 */
function withFooThis(\Closure $callback): void
{
}

// The closure is assumed to be scoped to its @param-closure-this class, as
// Closure::call() and Closure::bind($closure, $object, $object) would scope it,
// so self:: names that class even outside any class.
withFooThis(function (): void {
	assertType('ClosureBindScopeParamClosureThis\Foo', $this);
	assertType("'Foo'", self::A);
	assertType('ClosureBindScopeParamClosureThis\Foo', new self());
});

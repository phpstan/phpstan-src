<?php // lint >= 8.5

namespace ClosureGetCurrent;

use Closure;
use function PHPStan\Testing\assertType;

function doFoo(): void {
	assertType('*NEVER*', Closure::getCurrent());
}

function (int $i): string {
	// Closure::getCurrent() reads the closure scope's own reflection mid-body,
	// which carries the declared return type - the body-inferred 'foo' is not yet
	// available from the single body walk at this point.
	assertType('Closure(int): string', Closure::getCurrent());

	return 'foo';
};

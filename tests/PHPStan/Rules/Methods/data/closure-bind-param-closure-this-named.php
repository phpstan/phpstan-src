<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClosureBindParamClosureThisNamed;

use Closure;

class Foo
{

	/**
	 * @param-closure-this \stdClass $c
	 */
	public function doBar(\Closure $c): void
	{
		// $c is the new $this here, not the bound closure
		Closure::bind(newThis: $c, closure: function (): void {

		}); // ok
		Closure::bind(newThis: $c, closure: function (): void {

		}, newScope: null); // ok
	}

}

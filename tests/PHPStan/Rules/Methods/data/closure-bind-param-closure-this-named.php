<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClosureBindParamClosureThisNamed;

use Closure;

class Foo
{

	/**
	 * @param-closure-this \stdClass $c
	 */
	public function doFoo(\Closure $c): void
	{
		Closure::bind(closure: $c, newThis: new \stdClass()); // ok
		Closure::bind(newThis: new \stdClass(), closure: $c); // ok
		Closure::bind(closure: $c, newThis: new self()); // error
		Closure::bind(newThis: new self(), closure: $c); // error
		Closure::bind($c, newThis: new self()); // error
		Closure::bind(newScope: self::class, newThis: new self(), closure: $c); // error
		Closure::bind(newThis: new self(), newScope: null, closure: $c); // error
	}

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

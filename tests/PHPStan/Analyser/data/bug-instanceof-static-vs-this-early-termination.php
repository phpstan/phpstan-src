<?php declare(strict_types=1);

namespace BugInstanceofStaticVsThisEarlyTermination;

class FooBase
{
	public function run(): void
	{
		if ($this instanceof FooChild) {
			$foo = 1;

			if (rand(0, 1)) {
				$foo = 'a';
				$this->terminate();
			}

			if (rand(0, 1)) {
				$foo = 'b';
				$this::terminate();
			}

			if (rand(0, 1)) {
				$foo = 'c';
				static::terminate();
			}

			\PHPStan\Testing\assertType('1', $foo);
		}

		if (is_a(static::class, FooChild::class, true)) {
			$foo = 1;

			if (rand(0, 1)) {
				$foo = 'a';
				$this->terminate();
			}

			if (rand(0, 1)) {
				$foo = 'b';
				$this::terminate();
			}

			if (rand(0, 1)) {
				$foo = 'c';
				static::terminate();
			}

			\PHPStan\Testing\assertType('1', $foo);
		}
	}
}

class FooChild extends FooBase
{
	// registered as earlyTerminatingMethodCalls in NodeScopeResolverTest
	public static function terminate()
	{
	}
}

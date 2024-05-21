<?php declare(strict_types=1);

namespace BugInstanceofStaticVsThisPropAssignScope;

class FooBase
{
	public function run(): void
	{
		if ($this instanceof FooChild) {
			$this::$prop = 5;
			\PHPStan\Testing\assertType('5', $this::$prop);

			static::$prop = 6;
			\PHPStan\Testing\assertType('6', static::$prop);
		}

		if (is_a(static::class, FooChild::class, true)) {
			$this::$prop = 5;
			\PHPStan\Testing\assertType('5', $this::$prop);

			static::$prop = 6;
			\PHPStan\Testing\assertType('6', static::$prop);
		}
	}
}

class FooChild extends FooBase
{
	public static $prop;
}

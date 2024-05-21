<?php declare(strict_types=1);

namespace BugInstanceofStaticVsThisTypeSpecifier;

interface FooInterface
{
	/** @phpstan-assert-if-true null $v */
	public static function isNull(?int $v): bool;

	public static function foo(): int;
}

class FooBase
{
	public function bar(?int $v): void
	{
		if ($this instanceof FooInterface) {
			if ($this->isNull($v)) \PHPStan\Testing\assertType('null', $v);
			if ($this::isNull($v)) \PHPStan\Testing\assertType('null', $v);
			if (static::isNull($v)) \PHPStan\Testing\assertType('null', $v);

			if ($this::foo() === 5) \PHPStan\Testing\assertType('5', $this::foo());
			if ($this->foo() === 5) \PHPStan\Testing\assertType('5', $this->foo());
			if (static::foo() === 5) \PHPStan\Testing\assertType('5', static::foo());
		}

		if (is_a(static::class, FooInterface::class, true)) {
			if ($this->isNull($v)) \PHPStan\Testing\assertType('null', $v);
			if ($this::isNull($v)) \PHPStan\Testing\assertType('null', $v);
			if (static::isNull($v)) \PHPStan\Testing\assertType('null', $v);

			if ($this::foo() === 5) \PHPStan\Testing\assertType('5', $this::foo());
			if ($this->foo() === 5) \PHPStan\Testing\assertType('5', $this->foo());
			if (static::foo() === 5) \PHPStan\Testing\assertType('5', static::foo());
		}
	}
}

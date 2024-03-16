<?php declare(strict_types=1); // lint >= 7.4

namespace BugInstanceofStaticVsThis;

interface FooInterface
{
	public static function foo(): int;
}

class FooBase
{
	use FooTrait;

	public function bar(): void
	{
		if ($this instanceof FooInterface) {
			\PHPStan\Testing\assertType('$this(BugInstanceofStaticVsThis\FooBase)&BugInstanceofStaticVsThis\FooInterface', $this);
			\PHPStan\Testing\assertType('class-string<BugInstanceofStaticVsThis\FooInterface&static(BugInstanceofStaticVsThis\FooBase)>', static::class);
			\PHPStan\Testing\assertType('int', $this::foo());
			\PHPStan\Testing\assertType('int', $this->foo());
			\PHPStan\Testing\assertType('int', static::foo());

			\PHPStan\Testing\assertNativeType('$this(BugInstanceofStaticVsThis\FooBase)&BugInstanceofStaticVsThis\FooInterface', $this);
			\PHPStan\Testing\assertNativeType('class-string<BugInstanceofStaticVsThis\FooInterface&static(BugInstanceofStaticVsThis\FooBase)>', static::class);
			\PHPStan\Testing\assertNativeType('int', $this::foo());
			\PHPStan\Testing\assertNativeType('int', $this->foo());
			\PHPStan\Testing\assertNativeType('int', static::foo());
		}

		if (is_a(static::class, FooInterface::class, true)) {
			\PHPStan\Testing\assertType('$this(BugInstanceofStaticVsThis\FooBase)&BugInstanceofStaticVsThis\FooInterface', $this);
			\PHPStan\Testing\assertType('class-string<BugInstanceofStaticVsThis\FooInterface&static(BugInstanceofStaticVsThis\FooBase)>', static::class);
			\PHPStan\Testing\assertType('int', $this::foo());
			\PHPStan\Testing\assertType('int', $this->foo());
			\PHPStan\Testing\assertType('int', static::foo());

			\PHPStan\Testing\assertNativeType('$this(BugInstanceofStaticVsThis\FooBase)&BugInstanceofStaticVsThis\FooInterface', $this);
			\PHPStan\Testing\assertNativeType('class-string<BugInstanceofStaticVsThis\FooInterface&static(BugInstanceofStaticVsThis\FooBase)>', static::class);
			\PHPStan\Testing\assertNativeType('int', $this::foo());
			\PHPStan\Testing\assertNativeType('int', $this->foo());
			\PHPStan\Testing\assertNativeType('int', static::foo());
		}
	}
}

final class FooChild extends FooBase
{
	public const CONSTANT = 'a';
	public static int $staticProp = 5;
	public string $prop = 'b';
}

trait FooTrait
{
	public function baz(): void
	{
		if ($this instanceof FooChild) {
			\PHPStan\Testing\assertType("'a'", static::CONSTANT);
			\PHPStan\Testing\assertType("'a'", $this::CONSTANT);

			\PHPStan\Testing\assertType("int", static::$staticProp);
			\PHPStan\Testing\assertType("int", $this::$staticProp);

			\PHPStan\Testing\assertType("string", $this->prop);

			\PHPStan\Testing\assertNativeType("'a'", static::CONSTANT);
			\PHPStan\Testing\assertNativeType("'a'", $this::CONSTANT);

			\PHPStan\Testing\assertNativeType("int", static::$staticProp);
			\PHPStan\Testing\assertNativeType("int", $this::$staticProp);

			\PHPStan\Testing\assertNativeType("string", $this->prop);
		}

		if (is_a(static::class, FooChild::class, true)) {
			\PHPStan\Testing\assertType("'a'", static::CONSTANT);
			\PHPStan\Testing\assertType("'a'", $this::CONSTANT);

			\PHPStan\Testing\assertType("int", static::$staticProp);
			\PHPStan\Testing\assertType("int", $this::$staticProp);

			\PHPStan\Testing\assertType("string", $this->prop);

			\PHPStan\Testing\assertNativeType("'a'", static::CONSTANT);
			\PHPStan\Testing\assertNativeType("'a'", $this::CONSTANT);

			\PHPStan\Testing\assertNativeType("int", static::$staticProp);
			\PHPStan\Testing\assertNativeType("int", $this::$staticProp);

			\PHPStan\Testing\assertNativeType("string", $this->prop);
		}
	}
}

<?php

namespace PureMethod;

final class Foo
{

	/**
	 * @phpstan-pure
	 */
	public function doFoo(&$p)
	{
		echo 'test';
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo2(): void
	{
		die;
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo3(object $obj)
	{
		$obj->foo = 'test';
	}

	public function voidMethod(): void
	{
		echo '1';
	}

	/**
	 * @phpstan-impure
	 */
	public function impureVoidMethod(): void
	{
		echo '';
	}

	public function returningMethod(): int
	{

	}

	/**
	 * @phpstan-pure
	 */
	public function pureReturningMethod(): int
	{

	}

	/**
	 * @phpstan-impure
	 */
	public function impureReturningMethod(): int
	{
		echo '';
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo4()
	{
		$this->voidMethod();
		$this->impureVoidMethod();
		$this->returningMethod();
		$this->pureReturningMethod();
		$this->impureReturningMethod();
		$this->unknownMethod();
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo5()
	{
		self::voidMethod();
		self::impureVoidMethod();
		self::returningMethod();
		self::pureReturningMethod();
		self::impureReturningMethod();
		self::unknownMethod();
	}


}

final class PureConstructor
{

	/**
	 * @phpstan-pure
	 */
	public function __construct()
	{

	}

}

final class ImpureConstructor
{

	/**
	 * @phpstan-impure
	 */
	public function __construct()
	{
		echo '';
	}

}

final class PossiblyImpureConstructor
{

	public function __construct()
	{

	}

}

final class TestConstructors
{

	/**
	 * @phpstan-pure
	 */
	public function doFoo(string $s)
	{
		new PureConstructor();
		new ImpureConstructor();
		new PossiblyImpureConstructor();
		new $s();
	}

}

final class ActuallyPure
{

	/**
	 * @phpstan-impure
	 */
	public function doFoo()
	{

	}

}

class ToBeExtended
{

	/** @phpstan-pure */
	public function pure(): int
	{

	}

	/** @phpstan-impure */
	public function impure(): int
	{
		echo 'test';
		return 1;
	}

}

final class ExtendingClass extends ToBeExtended
{

	public function pure(): int
	{
		echo 'test';
		return 1;
	}

	public function impure(): int
	{
		return 1;
	}

}

final class ClassWithVoidMethods
{

	public function voidFunctionThatThrows(): void
	{
		if (rand(0, 1)) {
			throw new \Exception();
		}
	}

	public function emptyVoidFunction(): void
	{

	}

	protected function protectedEmptyVoidFunction(): void
	{

	}

	private function privateEmptyVoidFunction(): void
	{
		$a = 1 + 1;
	}

	private function setPostAndGet(array $post = [], array $get = []): void
	{
		$_POST = $post;
		$_GET = $get;
	}

	/**
	 * @phpstan-pure
	 */
	public function purePostGetAssign(array $post = [], array $get = []): int
	{
		$_POST = $post;
		$_GET = $get;

		return 1;
	}

}

final class NoMagicMethods
{

}

final class PureMagicMethods
{

	/**
	 * @phpstan-pure
	 */
	public function __toString(): string
	{
		return 'one';
	}

}

final class MaybePureMagicMethods
{

	public function __toString(): string
	{
		return 'one';
	}

}

final class ImpureMagicMethods
{

	/**
	 * @phpstan-impure
	 */
	public function __toString(): string
	{
		sleep(1);
		return 'one';
	}

}

final class TestMagicMethods
{

	/**
	 * @phpstan-pure
	 */
	public function doFoo(
		NoMagicMethods $no,
		PureMagicMethods $pure,
		MaybePureMagicMethods $maybe,
		ImpureMagicMethods $impure
	)
	{
		(string) $no;
		(string) $pure;
		(string) $maybe;
		(string) $impure;
	}

}

final class NoConstructor
{

}

final class TestNoConstructor
{

	/**
	 * @phpstan-pure
	 */
	public function doFoo(): int
	{
		new NoConstructor();

		return 1;
	}

}

final class MaybeCallableFromUnion
{

	/**
	 * @phpstan-pure
	 * @param callable|string $p
	 */
	public function doFoo($p): int
	{
		$p();

		return 1;
	}

}

final class VoidMethods
{

	private function doFoo(): void
	{

	}

	private function doBar(): void
	{
		\PHPStan\dumpType(1);
	}

	private function doBaz(): void
	{
		// nop
		;

		// nop
		;

		// nop
		;
	}

}

final class AssertingImpureVoidMethod
{

	/**
	 * @param mixed $value
	 * @phpstan-assert array $value
	 * @phpstan-impure
	 */
	public function assertSth($value): void
	{

	}

}

final class StaticMethodAccessingStaticProperty
{
	/** @var int */
	public static $a = 0;
	/**
	 * @phpstan-pure
	 */
	public static function getA(): int
	{
		return self::$a;
	}

	/**
	 * @phpstan-impure
	 */
	public static function getB(): int
	{
		return self::$a;
	}
}

final class StaticMethodAssigningStaticProperty
{
	/** @var int */
	public static $a = 0;
	/**
	 * @phpstan-pure
	 */
	public static function getA(): int
	{
		self::$a = 1;

		return 1;
	}

	/**
	 * @phpstan-impure
	 */
	public static function getB(): int
	{
		self::$a = 1;

		return 1;
	}
}

class CallDateTime
{

	/**
	 * @phpstan-pure
	 */
	public function doFoo(\DateTimeInterface $date): string
	{
		return $date->format('j. n. Y');
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo2(\DateTime $date): string
	{
		return $date->format('j. n. Y');
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo3(\DateTimeImmutable $date): string
	{
		return $date->format('j. n. Y');
	}

}

<?php

namespace PureMethod;

class Foo
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

class PureConstructor
{

	/**
	 * @phpstan-pure
	 */
	public function __construct()
	{

	}

}

class ImpureConstructor
{

	/**
	 * @phpstan-impure
	 */
	public function __construct()
	{
		echo '';
	}

}

class PossiblyImpureConstructor
{

	public function __construct()
	{

	}

}

class TestConstructors
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

class ActuallyPure
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

class ExtendingClass extends ToBeExtended
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

class ClassWithVoidMethods
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

class NoMagicMethods
{

}

class PureMagicMethods
{

	/**
	 * @phpstan-pure
	 */
	public function __toString(): string
	{
		return 'one';
	}

}

class MaybePureMagicMethods
{

	public function __toString(): string
	{
		return 'one';
	}

}

class ImpureMagicMethods
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

class TestMagicMethods
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

class NoConstructor
{

}

class TestNoConstructor
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

class MaybeCallableFromUnion
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

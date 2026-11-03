<?php // lint >= 8.0

namespace UnnecessaryTernaryCalls;

class Foo
{

	/**
	 * @phpstan-pure
	 * @return null
	 */
	public function pureNull()
	{
		return null;
	}

	/**
	 * @phpstan-pure
	 * @return null
	 */
	public static function staticPureNull()
	{
		return null;
	}

	/** @return null */
	public function maybeImpureNull()
	{
		return null;
	}

	/**
	 * @phpstan-pure
	 * @return ($s is string ? int<1, max> : 0)
	 */
	public function size(string $s): int
	{
		return strlen($s) + 1;
	}

	public function pureMethod(?Foo $foo): ?Foo
	{
		return $foo ?: $this->pureNull();
	}

	public function pureStaticMethod(?Foo $foo): ?Foo
	{
		return $foo ?: self::staticPureNull();
	}

	public function maybeImpureMethod(?Foo $foo): ?Foo
	{
		return $foo ?: $this->maybeImpureNull();
	}

	public function nullableReceiver(?Foo $foo, ?Foo $other): ?Foo
	{
		return $foo ?: $other->pureNull();
	}

	public function sizeOfFalse(string|false $s): int
	{
		return $s === false ? 0 : $this->size($s);
	}

	public static function staticSizeOfFalse(string|false $s): int
	{
		return $s === false ? 0 : self::staticSize($s);
	}

	/**
	 * @phpstan-pure
	 * @return ($s is string ? int<1, max> : 0)
	 */
	public static function staticSize(string $s): int
	{
		return strlen($s) + 1;
	}

}

/**
 * @param pure-callable(): null $cb
 */
function pureCallable(?Foo $foo, callable $cb): ?Foo
{
	return $foo ?: $cb();
}

/**
 * @param callable(): null $cb
 */
function impureCallable(?Foo $foo, callable $cb): ?Foo
{
	return $foo ?: $cb();
}

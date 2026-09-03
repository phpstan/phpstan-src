<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15128;

use Override;
use function PHPStan\Testing\assertType;

/**
 * @template T of int|string
 */
class A
{

	/**
	 * @param T $val
	 */
	public function __construct(
		protected int|string $val,
	)
	{
	}

	/**
	 * @return (T is int ? string : int)
	 */
	public function foo(): int|string
	{
		return is_string($this->val) ? intval($this->val) : (string) $this->val;
	}

	/**
	 * @param T $val
	 * @return ($val is int ? string : int)
	 */
	public function bar($val)
	{
		throw new \Exception();
	}

}

/**
 * @extends A<string>
 */
class B extends A
{

	#[Override]
	public function foo(): int
	{
		return intval($this->val);
	}

	#[Override]
	public function bar($val)
	{
		throw new \Exception();
	}

}

/**
 * @extends A<int|string>
 */
class C extends A
{

	#[Override]
	public function foo(): int|string
	{
		return is_string($this->val) ? intval($this->val) : (string) $this->val;
	}

	#[Override]
	public function bar($val)
	{
		throw new \Exception();
	}

}

/**
 * @template U of int|string
 * @extends A<U>
 */
class D extends A
{

	#[Override]
	public function foo(): int|string
	{
		return is_string($this->val) ? intval($this->val) : (string) $this->val;
	}

	#[Override]
	public function bar($val)
	{
		throw new \Exception();
	}

}

/**
 * @param B $b
 * @param C $c
 * @param D<string> $d
 */
function test($b, $c, $d): void
{
	assertType('int', $b->foo());
	assertType('int', $b->bar('foo'));

	assertType('int|string', $c->foo());
	assertType('int', $c->bar('foo'));
	assertType('string', $c->bar(1));

	assertType('int', $d->foo());
	assertType('int', $d->bar('foo'));
}

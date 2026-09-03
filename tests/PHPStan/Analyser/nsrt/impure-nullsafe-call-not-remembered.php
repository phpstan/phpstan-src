<?php declare(strict_types = 1);

namespace ImpureNullsafeCallNotRemembered;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @phpstan-impure */
	public function impure(): bool
	{
		return (bool) microtime(true);
	}

	/** @phpstan-pure */
	public function pure(): bool
	{
		return true;
	}

	/** @phpstan-pure */
	public function getSelf(): ?self
	{
		return $this;
	}

	public function doImpure(?self $foo): void
	{
		// neither the `?->` key nor its plain twin remembers an impure value
		if ($foo?->impure()) {
			assertType('ImpureNullsafeCallNotRemembered\Foo', $foo);
			assertType('bool', $foo?->impure());
			assertType('bool', $foo->impure());
		}
	}

	public function doImpureChain(?self $foo): void
	{
		if ($foo?->getSelf()?->impure()) {
			assertType('ImpureNullsafeCallNotRemembered\Foo', $foo);
			assertType('bool', $foo?->getSelf()?->impure());
			assertType('bool', $foo->getSelf()->impure());
		}
	}

	public function doPure(?self $foo): void
	{
		if ($foo?->pure()) {
			assertType('true', $foo?->pure());
			assertType('true', $foo->pure());
		}
	}

	public function doPureChain(?self $foo): void
	{
		if ($foo?->getSelf()?->pure()) {
			assertType('true', $foo?->getSelf()?->pure());
			assertType('true', $foo->getSelf()->pure());
		}
	}

}

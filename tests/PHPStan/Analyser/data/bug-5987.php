<?php declare(strict_types=1);

namespace Bug5987;

class A
{
	/** @return 'a' */
	public function getScope(): string { return 'a'; }
}

class B extends A
{
	/** @return 'b' */
	public function getScope(): string { return 'b'; } // @phpstan-ignore-line
	/** @return 'b' */
	private function getScopePriv(): string { return 'b'; } // @phpstan-ignore-line
}

class C extends B
{
	/** @return 'c' */
	public function getScope(): string { return 'c'; } // @phpstan-ignore-line

	public function test(): void
	{
		\Closure::bind(function () {
			\PHPStan\Testing\assertType("'Bug5987\\\\B'", self::class);
			\PHPStan\Testing\assertType("'b'", self::getScope());
			\PHPStan\Testing\assertType("'b'", (self::class)::getScope());

			\PHPStan\Testing\assertType('class-string<static(Bug5987\\C)>', static::class);
			\PHPStan\Testing\assertType("'c'", static::getScope());
			\PHPStan\Testing\assertType("'c'", (static::class)::getScope());

			\PHPStan\Testing\assertType("'Bug5987\\\\A'", parent::class);
			\PHPStan\Testing\assertType("'a'", parent::getScope());
			\PHPStan\Testing\assertType("'a'", (parent::class)::getScope());

			\PHPStan\Testing\assertType("'c'", $this->getScope());
			\PHPStan\Testing\assertType("'b'", $this->getScopePriv());
		}, $this, B::class)();
	}
}

$c = new C();
$c->test();

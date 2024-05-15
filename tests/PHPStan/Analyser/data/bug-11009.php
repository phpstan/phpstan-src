<?php

namespace Bug11009;

use function PHPStan\Testing\assertType;

trait A
{
	public static function callbackStatic(callable $cb): void
	{
	}

	public static function callbackSelf(callable $cb): void
	{
	}

	public function returnStatic()
	{
		return $this;
	}

	public function returnSelf()
	{
		return new self;
	}
}

class B
{
	use A;
}

function (): void {
	B::callbackStatic(function (): void {
		assertType(B::class, $this);
	});

	B::callbackSelf(function (): void {
		assertType(B::class, $this);
	});

	$b = new B();
	assertType(B::class, $b->returnStatic());
	assertType(B::class, $b->returnSelf());
};

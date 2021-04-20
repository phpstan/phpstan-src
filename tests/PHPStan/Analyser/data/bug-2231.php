<?php

namespace Bug2231;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(?Foo $x): void
	{
		if ($x !== null && !($x instanceof static)) {
			throw new \TypeError('custom error');
		}

		assertType('static(Bug2231\Foo)|null', $x);
	}

	public function doBar(?Foo $x): void
	{
		if ($x !== null && !($x instanceof self)) {
			throw new \TypeError('custom error');
		}

		assertType('Bug2231\Foo|null', $x);
	}

	public function doBaz($x): void
	{
		if ($x instanceof self) {
			assertType('Bug2231\Foo', $x);
		}

		if ($x instanceof static) {
			assertType('static(Bug2231\Foo)', $x);
		}
	}

}

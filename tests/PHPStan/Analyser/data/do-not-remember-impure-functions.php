<?php

namespace DoNotRememberImpureFunctions;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo()
	{
		function (): void {
			if (rand(0, 1)) {
				assertType('int<0, 1>', rand(0, 1));
			}
		};

		function (): void {
			if (rand(0, 1) === 0) {
				assertType('int<0, 1>', rand(0, 1));
			}
		};
		function (): void {
			assertType('1|\'foo\'', rand(0, 1) ?: 'foo');
			assertType('\'foo\'|int<0, 1>', rand(0, 1) ? rand(0, 1) : 'foo');
		};
	}

	public function doBar(): bool
	{

	}

	/** @phpstan-pure */
	public function doBaz(): bool
	{

	}

	/** @phpstan-impure */
	public function doLorem(): bool
	{

	}

	public function doIpsum()
	{
		if ($this->doBar() === true) {
			assertType('true', $this->doBar());
		}

		if ($this->doBaz() === true) {
			assertType('true', $this->doBaz());
		}

		if ($this->doLorem() === true) {
			assertType('bool', $this->doLorem());
		}
	}

	public function doDolor()
	{
		if ($this->doBar()) {
			assertType('true', $this->doBar());
		}

		if ($this->doBaz()) {
			assertType('true', $this->doBaz());
		}

		if ($this->doLorem()) {
			assertType('bool', $this->doLorem());
		}
	}

}

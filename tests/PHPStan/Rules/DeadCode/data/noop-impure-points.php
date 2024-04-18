<?php

namespace NoopImpurePoints;

class Foo
{

	public function doFoo(bool $b): void
	{
		$b && $this->doBar();
		$b && $this->doBaz();
		$b && $this->doLorem();
	}

	/**
	 * @phpstan-pure
	 */
	public function doBar(): bool
	{
		return true;
	}

	/**
	 * @phpstan-impure
	 */
	public function doBaz(): bool
	{
		return true;
	}

	public function doLorem(): bool
	{
		return true;
	}

	public function doExit(): void
	{
		exit(1);
	}

	public function doAssign(bool $b): void
	{
		$b ? $a = 1 : '';
		$b ? $this->foo = 1 : '';
	}

	public function doClosures(): void
	{
		$a = static function () {
			echo '1';
		};
		$a();

		$b = static function () {
			return 1 + 1;
		};
		$b();
	}

}

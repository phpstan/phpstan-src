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

}

<?php

namespace WrongVarNativeType;

class Foo
{

	public function doFoo(): void
	{
		/** @var 'a' $a */
		$a = $this->doBar();

		/** @var string|null $stringOrNull */
		$stringOrNull = $this->doBar();

		/** @var string|null $null */
		$null = null;

		/** @var \SplObjectStorage<\stdClass, array{int, string}> $running */
		$running = new \SplObjectStorage();

		/** @var \stdClass $running2 */
		$running2 = new \SplObjectStorage();

		/** @var int $int */
		$int = 'foo';

		/** @var int $test */
		$test = $this->doBaz();
	}

	public function doBar(): string
	{

	}

	/**
	 * @return string
	 */
	public function doBaz()
	{

	}

}

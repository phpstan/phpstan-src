<?php

namespace ArrayDestructuringTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @var int */
	private $foo;

	public function doFoo()
	{
		[$this->foo] = [1];
		assertType('1', $this->foo);
	}

	public function doBar()
	{
		foreach ([1, 2, 3] as $key => $this->foo) {
			assertType('0|1|2', $key);
			assertType('1|2|3', $this->foo);
		}
	}

	public function doBaz()
	{
		foreach ([[1], [2], [3]] as $key => [$this->foo]) {
			assertType('0|1|2', $key);
			assertType('1|2|3', $this->foo);
		}
	}

	public function doLorem()
	{
		foreach ([[1]] as $key => [$this->foo]) {
			assertType('0', $key);
			assertType('1', $this->foo);
		}
	}

}

class Bar
{

	public function doFoo()
	{

		$matrix = $this->preprocessOpeningHours();
		if ($matrix === []) {
			return null;
		}

		/** @var string[][] $matrix */
		$matrix[] = end($matrix);

		assertType('array<array<string>>', $matrix);
	}

	/**
	 * @return string[][]
	 */
	private function preprocessOpeningHours(): array
	{
		return [];
	}

}

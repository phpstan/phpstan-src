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

		/** @var array<int> $ints */
		$ints = $this->returnsListOfIntegers();

		/** @var array<string> $strings */
		$strings = $this->returnsListOfIntegers();

		/** @var \Iterator<int> $intIterator */
		$intIterator = $this->returnsListOfIntegers();

		/** @var \Iterator<int> $intIterator */
		$intIterator2 = $this->returnsIteratorOfIntegers();

		/** @var \Iterator<string> $stringIterator */
		$stringIterator = $this->returnsIteratorOfIntegers();

		/** @var int[] $ints2 */
		$ints2 = $this->returnsArrayOfIntegers();
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

	/**
	 * @return list<int>
	 */
	public function returnsListOfIntegers(): array
	{

	}

	/**
	 * @return \Iterator<int, int>
	 */
	public function returnsIteratorOfIntegers(): \Iterator
	{

	}

	/** @return array<int, int> */
	public function returnsArrayOfIntegers(): array
	{

	}

}

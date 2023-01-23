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

	/** @param int[] $integers */
	public function trickyForeachCase(array $integers): void
	{
		foreach ($integers as $int) {
			/** @var int $int */
			$a = new \stdClass();
		}

		foreach ($integers as $int) {
			/** @var string $int */
			$a = new \stdClass();
		}

		/** @var string */
		$nameless = 1;
	}

	public function testArrayDestructuring(int $i, string $s): void
	{
		/**
		 * @var int $a
		 * @var string $b
		 * @var int $c
		 */
		[$a, $b, $c] = [$i, $s, $s];
	}

	/**
	 * @param array<int, string> $a
	 */
	public function testForeach(array $a): void
	{
		/**
		 * @var string[] $a
		 * @var int $k
		 * @var string $v
		 */
		foreach ($a as $k => $v) {

		}
	}

	/**
	 * @param array<int, string> $a
	 */
	public function testForeach2(array $a): void
	{
		/**
		 * @var int[] $a
		 * @var string $k
		 * @var int $v
		 */
		foreach ($a as $k => $v) {

		}
	}

	public function testStatic(): void
	{
		/** @var int $a */
		static $a = 1;

		/** @var int $b */
		static $b = 'foo';
	}

	public function iterablesRecursively(): void
	{
		/** @var array<array<string>> $a */
		$a = $this->arrayOfLists();

		/** @var array<array<string>> $b */
		$b = $this->arrayOfLists();

		/** @var array<array<int>> $c */
		$c = $this->arrayOfLists();

		/** @var array<\Traversable<string>> $d */
		$d = $this->arrayOfLists();
	}

	/** @return array<list<string|null>> */
	private function arrayOfLists(): array
	{

	}

}

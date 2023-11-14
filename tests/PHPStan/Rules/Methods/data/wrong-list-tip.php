<?php

namespace WrongListTip;

interface Foo
{

}

interface Bar
{

}

class Test
{

	/**
	 * @return list<Foo>
	 */
	public function doFoo(): array
	{
		return $this->listOfBars();
	}

	/**
	 * @return list<Bar>
	 */
	public function listOfBars(): array
	{
		return [];
	}

}

class Test2
{

	/**
	 * @return non-empty-array<Foo>
	 */
	public function doFoo(): array
	{
		return $this->nonEmptyArrayOfBars();
	}

	/**
	 * @return non-empty-array<Bar>
	 */
	public function nonEmptyArrayOfBars(): array
	{
		/** @var Bar $b */
		$b = doFoo();
		return [$b];
	}

}

class Test3
{

	/**
	 * @return non-empty-list<Foo>
	 */
	public function doFoo(): array
	{
		return $this->nonEmptyArrayOfBars();
	}

	/**
	 * @return array<Bar>
	 */
	public function nonEmptyArrayOfBars(): array
	{
		return [];
	}

}

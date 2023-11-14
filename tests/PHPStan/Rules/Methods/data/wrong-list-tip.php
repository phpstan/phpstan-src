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

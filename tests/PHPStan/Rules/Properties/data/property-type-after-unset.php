<?php

namespace PropertyTypeAfterUnset;

class Foo
{

	/** @var non-empty-array<int> */
	private $nonEmpty;

	/** @var list<int> */
	private $listProp;

	/** @var array<list<int>> */
	private $nestedListProp;

	public function doFoo(int $i, int $j)
	{
		unset($this->nonEmpty[$i]);
		unset($this->listProp[$i]);
		unset($this->nestedListProp[$i][$j]);
	}

}

class Bar
{

	/** @var array<string, array<string, Foo>> */
	private $prop;

	/**
	 * @param int|string $key
	 */
	public function doFoo($key): void
	{
		unset($this->prop[$key]['foo']);
	}

}

<?php

namespace Levels\ListType;

class Foo
{

	/** @var list<string> */
	public $list;

	/**
	 * @param array<string, string> $stringKeyArray
	 * @param array<int, string> $intKeyArray
	 * @param list<string|null> $stringOrNullList
	 * @param list<string> $stringList
	 */
	public function doFoo(
		array $stringKeyArray,
		array $intKeyArray,
		array $stringOrNullList,
		array $stringList
	): void
	{
		$this->list = $stringKeyArray;
		$this->list = $intKeyArray;
		$this->list = $stringOrNullList;
		$this->list = $stringList;
	}

}

<?php

namespace ReturnList;

class Foo
{
	/** @return list<string> */
	public function getList1(): array
	{
		return array_filter(['foo', 'bar'], 'file_exists');
	}

	/**
	 * @param array<int, string> $array
	 * @return list<string>
	 */
	public function getList2(array $array): array
	{
		return array_intersect_key(['foo', 'bar'], $array);
	}
}

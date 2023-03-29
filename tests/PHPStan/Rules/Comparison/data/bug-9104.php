<?php

namespace Bug9104;

class ArrayLibrary
{
	/**
	 * @param non-empty-list<int> $list
	 */
	public function getFirst(array $list): int
	{
		if (count($list) === 0) {
			throw new \LogicException('empty array');
		}

		return $list[0];
	}
}

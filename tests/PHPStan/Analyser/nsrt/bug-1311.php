<?php

namespace Bug1311;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @var array<int, string>
	 */
	private $list = [];

	/**
	 * @param array<int, int> $temp
	 */
	public function convertList(array $temp): void
	{
		foreach ($temp as &$item) {
			$item = (string) $item;
		}

		assertType('array<int, lowercase-string&numeric-string&uppercase-string>', $temp);

		$this->list = $temp;
	}
}

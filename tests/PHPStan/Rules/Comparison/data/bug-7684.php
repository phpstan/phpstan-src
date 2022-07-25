<?php declare(strict_types = 1);

namespace Bug7684;

class SomeClass
{
	public function someFunction(int ...$someIntArray): void
	{
		$arr = [];
		foreach ($someIntArray as $val) {
			if (in_array($val, $arr, true) === true) {
				continue;
			}

			$arr[] = $val;
		}
	}
}

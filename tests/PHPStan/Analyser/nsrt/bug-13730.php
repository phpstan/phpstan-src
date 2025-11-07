<?php declare(strict_types = 1);

namespace Bug13730;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param array<string|null> $arr
	 * @return array<string>
	 */
	public function sayHello(array $arr): array
	{
		foreach($arr as $k => $v) {
			$arr[$k] = $v ?? '';
		}

		assertType('array<string>', $arr);

		return $arr;
	}
}

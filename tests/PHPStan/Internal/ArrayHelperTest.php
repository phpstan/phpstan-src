<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPUnit\Framework\TestCase;

class ArrayHelperTest extends TestCase
{

	public function testUnsetKeyAtPath(): void
	{
		$array = [
			'dep1a' => [
				'dep2a' => [
					'dep3a' => null,
				],
				'dep2b' => null,
			],
			'dep1b' => null,
		];

		$path = ['dep1a', 'dep2a', 'dep3a'];
		ArrayHelper::unsetKeyAtPath($array, $path);

		$this->assertSame([
			'dep1a' => [
				'dep2a' => [],
				'dep2b' => null,
			],
			'dep1b' => null,
		], $array);
	}

}

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

		ArrayHelper::unsetKeyAtPath($array, ['dep1a', 'dep2a', 'dep3a']);

		$this->assertSame([
			'dep1a' => [
				'dep2a' => [],
				'dep2b' => null,
			],
			'dep1b' => null,
		], $array);

		ArrayHelper::unsetKeyAtPath($array, ['dep1a', 'dep2a']);

		$this->assertSame([
			'dep1a' => [
				'dep2b' => null,
			],
			'dep1b' => null,
		], $array);

		ArrayHelper::unsetKeyAtPath($array, ['dep1a']);

		$this->assertSame([
			'dep1b' => null,
		], $array);

		ArrayHelper::unsetKeyAtPath($array, ['dep1b']);

		$this->assertSame([], $array);
	}

	public function testUnsetKeyAtPathEmpty(): void
	{
		$array = [];

		ArrayHelper::unsetKeyAtPath($array, ['foo', 'bar']);

		$this->assertSame([], $array);
	}

}

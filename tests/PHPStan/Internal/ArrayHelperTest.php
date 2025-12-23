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

		self::assertSame([
			'dep1a' => [
				'dep2a' => [],
				'dep2b' => null,
			],
			'dep1b' => null,
		], $array);

		ArrayHelper::unsetKeyAtPath($array, ['dep1a', 'dep2a']);

		self::assertSame([
			'dep1a' => [
				'dep2b' => null,
			],
			'dep1b' => null,
		], $array);

		ArrayHelper::unsetKeyAtPath($array, ['dep1a']);

		self::assertSame([
			'dep1b' => null,
		], $array);

		ArrayHelper::unsetKeyAtPath($array, ['dep1b']);

		self::assertSame([], $array);
	}

	public function testUnsetKeyAtPathEmpty(): void
	{
		$array = [];

		ArrayHelper::unsetKeyAtPath($array, ['foo', 'bar']);

		self::assertSame([], $array);
	}

}

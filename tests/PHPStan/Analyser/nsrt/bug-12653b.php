<?php declare(strict_types = 1);

namespace Bug12653b;

use function PHPStan\Testing\assertType;

class WhileLoopTest
{
	const TYPE_XXX = 'xxx';
	const TYPE_YYY = 'yyy';
	const TYPE_ZZZ = 'zzz';

	/**
	 * @return array<'a'|'b'|'c'|'d', self::TYPE_*>
	 */
	public function whileLoop(): array
	{
		$list = [
			'a' => self::TYPE_XXX,
			'b' => self::TYPE_YYY,
			'c' => self::TYPE_ZZZ,
			'd' => self::TYPE_XXX,
		];

		$keys = ['a', 'b', 'c', 'd'];
		$found = false;
		$i = 0;
		while ($i < count($keys)) {
			$key = $keys[$i];
			if ($list[$key] === self::TYPE_XXX) {
				if (!$found) {
					$found = true;
				} else {
					$list[$key] = self::TYPE_ZZZ;
				}
			}
			$i++;
		}
		assertType("array{a: 'xxx'|'zzz', b: 'yyy'|'zzz', c: 'zzz', d: 'xxx'|'zzz'}", $list);

		return $list;
	}
}

class ForLoopTest
{
	const TYPE_XXX = 'xxx';
	const TYPE_YYY = 'yyy';
	const TYPE_ZZZ = 'zzz';

	/**
	 * @return array<'a'|'b'|'c'|'d', self::TYPE_*>
	 */
	public function forLoop(): array
	{
		$list = [
			'a' => self::TYPE_XXX,
			'b' => self::TYPE_YYY,
			'c' => self::TYPE_ZZZ,
			'd' => self::TYPE_XXX,
		];

		$keys = ['a', 'b', 'c', 'd'];
		$found = false;
		for ($i = 0; $i < count($keys); $i++) {
			$key = $keys[$i];
			if ($list[$key] === self::TYPE_XXX) {
				if (!$found) {
					$found = true;
				} else {
					$list[$key] = self::TYPE_ZZZ;
				}
			}
		}
		assertType("array{a: 'xxx'|'zzz', b: 'yyy'|'zzz', c: 'zzz', d: 'xxx'|'zzz'}", $list);

		return $list;
	}
}

class FloatConstantArrayTest
{
	const RATE_LOW = 0.5;
	const RATE_MED = 1.0;
	const RATE_HIGH = 1.5;

	/**
	 * @param list<'x'|'y'|'z'> $keys
	 */
	public function floatConstantsInArray(array $keys): void
	{
		$rates = [
			'x' => self::RATE_LOW,
			'y' => self::RATE_MED,
			'z' => self::RATE_HIGH,
		];

		$found = false;
		foreach ($keys as $key) {
			if ($rates[$key] === self::RATE_LOW) {
				if (!$found) {
					$found = true;
				} else {
					$rates[$key] = self::RATE_HIGH;
				}
			}
		}

		assertType("array{x: 0.5|1.5, y: 1.0|1.5, z: 1.5}", $rates);
	}
}

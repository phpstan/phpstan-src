<?php declare(strict_types=1);

namespace Bug13526;

class Foo
{
	public function foo(string $item, bool $a): ?int
	{
		$map1 = [
			'MAP_1_ITEM_1' => 1,
			'MAP_1_ITEM_2' => 2,
		];
		$map2 = [
			'MAP_2_ITEM_1' => 1,
			'MAP_2_ITEM_2' => 2,
		];

		$map = $a ? $map1 : $map2;

		if (array_key_exists($item, $map)) {
			return $map[$item];
		}

		return null;
	}
}

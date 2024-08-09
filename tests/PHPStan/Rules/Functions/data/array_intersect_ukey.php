<?php namespace ParamsArrayIntersectUkey;

array_intersect_ukey(
	['a' => 1, 'b' => 2],
	['c' => 1, 'd' => 2],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

array_intersect_ukey(
	[1, 2, 3],
	[1, 2, 4, 5],
	static function (int $a, int $b): int {
		return $a <=> $b;
	}
);

array_intersect_ukey(
	['a' => 1, 'b' => 2],
	['c' => 1, 'd' => 2],
	static function (int $a, int $b): int {
		return $a <=> $b;
	}
);

array_intersect_ukey(
	[1, 2, 3],
	[1, 2, 4, 5],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

array_intersect_ukey(
	['a' => 'a', 'b' => 'b'],
	['c' => 'c', 'd' => 'd'],
	['c' => 'c', 'd' => 'd'],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

array_intersect_ukey(
	['a' => 'a', 'b' => 'b'],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

array_intersect_ukey(
	['a' => 'a', 'b' => 'b'],
	static function (string $a, string $b): int {
		return $a <=> $b;
	},
	['a' => 'a', 'b' => 'b'],
);

array_intersect_ukey(
	['a' => 'a', 'b' => 'b'],
);

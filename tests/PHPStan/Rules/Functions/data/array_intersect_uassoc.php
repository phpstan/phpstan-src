<?php namespace ParamsArrayIntersectUassoc;

array_intersect_uassoc(
	['a' => 1, 'b' => 2],
	['c' => 1, 'd' => 2],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

array_intersect_uassoc(
	[1, 2, 3],
	[1, 2, 4, 5],
	static function (int $a, int $b): int {
		return $a <=> $b;
	}
);

array_intersect_uassoc(
	['a' => 1, 'b' => 2],
	['c' => 1, 'd' => 2],
	static function (int $a, int $b): int {
		return $a <=> $b;
	}
);

array_intersect_uassoc(
	[1, 2, 3],
	[1, 2, 4, 5],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

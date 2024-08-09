<?php namespace ParamsArrayUintersectAssoc;

array_uintersect_assoc(
	['a' => 'a', 'b' => 'b'],
	['c' => 'c', 'd' => 'd'],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

array_uintersect_assoc(
	[1, 2, 3],
	[1, 2, 3, 4],
	static function (int $a, int $b): int {
		return $a <=> $b;
	}
);

array_uintersect_assoc(
	['a' => 'a', 'b' => 'b'],
	['c' => 'c', 'd' => 'd'],
	static function (int $a, int $b): int {
		return $a <=> $b;
	}
);

array_uintersect_assoc(
	[1, 2, 3],
	[1, 2, 3, 4],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

array_uintersect_assoc(
	['a' => 'a', 'b' => 'b'],
	['c' => 'c', 'd' => 'd'],
	['c' => 'c', 'd' => 'd'],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

array_uintersect_assoc(
	['a' => 'a', 'b' => 'b'],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

array_uintersect_assoc(
	['a' => 'a', 'b' => 'b'],
	static function (string $a, string $b): int {
		return $a <=> $b;
	},
	['a' => 'a', 'b' => 'b'],
);

array_uintersect_assoc(
	['a' => 'a', 'b' => 'b'],
);

<?php

array_uintersect(
	['a', 'b'],
	['c', 'd'],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

array_uintersect(
	[1, 2, 3],
	[1, 2, 3, 4],
	static function (int $a, int $b): int {
		return $a <=> $b;
	}
);

array_uintersect(
	['a', 'b'],
	['c', 'd'],
	static function (int $a, int $b): int {
		return $a <=> $b;
	}
);

array_uintersect(
	[1, 2, 3],
	[1, 2, 3, 4],
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

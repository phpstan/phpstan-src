<?php

array_udiff_uassoc(
	['a' => 'a', 'b' => 'b'],
	['c' => 'c', 'd' => 'd'],
	static function (string $a, string $b): int {
		return $a <=> $b;
	},
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

array_udiff_uassoc(
	[1, 2, 3],
	[1, 2, 4, 5],
	static function (int $a, int $b): int {
		return $a <=> $b;
	},
	static function (int $a, int $b): int {
		return $a <=> $b;
	},
);

array_udiff_uassoc(
	['a' => 'a', 'b' => 'b'],
	['c' => 'c', 'd' => 'd'],
	static function (int $a, int $b): int {
		return $a <=> $b;
	},
	static function (int $a, int $b): int {
		return $a <=> $b;
	}
);

array_udiff_uassoc(
	[1, 2, 3],
	[1, 2, 4, 5],
	static function (string $a, string $b): int {
		return $a <=> $b;
	},
	static function (string $a, string $b): int {
		return $a <=> $b;
	}
);

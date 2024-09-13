<?php declare(strict_types = 1);

namespace ArrayDiffIntersectCallbacks;

use function PHPStan\Testing\assertType;

array_diff_uassoc(
	[1, 2],
	[3, 4],
	static function ($a, $b) {
		assertType('0|1', $a);
		assertType('0|1', $b);

		return 0;
	},
);

array_diff_ukey(
	[1, 2],
	[3, 4],
	static function ($a, $b) {
		assertType('0|1', $a);
		assertType('0|1', $b);

		return 0;
	},
);

array_intersect_uassoc(
	[1, 2],
	[3, 4],
	static function ($a, $b) {
		assertType('0|1', $a);
		assertType('0|1', $b);

		return 0;
	},
);

array_intersect_ukey(
	[1, 2],
	[3, 4],
	static function ($a, $b) {
		assertType('0|1', $a);
		assertType('0|1', $b);

		return 0;
	},
);

array_udiff(
	[1, 2],
	[3, 4],
	static function ($a, $b) {
		assertType('1|2|3|4', $a);
		assertType('1|2|3|4', $b);

		return 0;
	},
);

array_udiff_assoc(
	[1, 2],
	[3, 4],
	static function ($a, $b) {
		assertType('1|2|3|4', $a);
		assertType('1|2|3|4', $b);

		return 0;
	},
);

array_udiff_uassoc(
	[1, 2],
	[3, 4],
	static function ($a, $b) {
		assertType('1|2|3|4', $a);
		assertType('1|2|3|4', $b);

		return 0;
	},
	static function ($a, $b) {
		assertType('0|1', $a);
		assertType('0|1', $b);

		return 0;
	},
);

array_uintersect(
	[1, 2],
	[3, 4],
	static function ($a, $b) {
		assertType('1|2|3|4', $a);
		assertType('1|2|3|4', $b);

		return 0;
	},
);

array_uintersect_assoc(
	[1, 2],
	[3, 4],
	static function ($a, $b) {
		assertType('1|2|3|4', $a);
		assertType('1|2|3|4', $b);

		return 0;
	},
);

array_uintersect_uassoc(
	[1, 2],
	[3, 4],
	static function ($a, $b) {
		assertType('1|2|3|4', $a);
		assertType('1|2|3|4', $b);

		return 0;
	},
	static function ($a, $b) {
		assertType('0|1', $a);
		assertType('0|1', $b);

		return 0;
	},
);

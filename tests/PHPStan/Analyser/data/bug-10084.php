<?php // onlyif PHP_VERSION_ID >= 80000

namespace Bug10084;

use function PHPStan\Testing\assertType;

function (?int $val) {
	match ($val) {
		null => assertType('null', $val),
		default => assertType('int', $val),
	};
};

function (?int $val) {
	match ($foo = $val) {
		null => assertType('null', $val),
		default => assertType('int', $val),
	};
};

function (?int $val) {
	match ($foo = $val) {
		null => assertType('null', $foo),
		default => assertType('int', $foo),
	};
};

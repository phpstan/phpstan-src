<?php declare(strict_types = 1);

namespace Bug3770;

use function PHPStan\Testing\assertType;

// PHPDoc on closures should be respected for purity

/** @phpstan-impure */
$f = static function (string $input): bool {
	return strlen($input) > rand(0, 10);
};

if ($f('hello')) {
	// Should not narrow to true because closure is impure
	assertType('bool', $f('hello'));
}

// Closure with @phpstan-pure should allow narrowing
/** @phpstan-pure */
$g = static function (string $input): bool {
	return strlen($input) > 5;
};

if ($g('hello world')) {
	assertType('true', $g('hello world'));
}

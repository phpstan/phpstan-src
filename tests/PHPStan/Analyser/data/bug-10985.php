<?php // lint >= 8.1

namespace Bug10985;

use function PHPStan\Testing\assertType;

enum Test {
	case ORIGINAL;
}

function (): void {
	$item = Test::class;
	$result = ($item)::ORIGINAL;
	assertType('Bug10985\\Test::ORIGINAL', $result);
};

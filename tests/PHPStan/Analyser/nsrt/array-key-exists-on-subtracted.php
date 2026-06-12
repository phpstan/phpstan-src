<?php // lint >= 8.1

namespace ArrayKeyExistsSubtracted;

use function PHPStan\Testing\assertType;

enum IntBacked: int {
	case A = 1;
	case B = 2;
}

function test(IntBacked $i, array $arr): void {
	if ($i !== IntBacked::A) {
		assertType('2', $i->value);

		if (array_key_exists($i->value, $arr)) {
			assertType('non-empty-array&hasOffset(2)', $arr);
		}
	}
}

<?php

declare(strict_types = 1);

namespace Bug6663;

use function PHPStan\Testing\assertType;

class X {}
class Y {}

function test(mixed $xy, mixed $ab): void
{
	if ($xy instanceof X || $ab instanceof X) {
		if ($xy instanceof Y) {
			assertType('Bug6663\Y', $xy);
			assertType('Bug6663\X', $ab);
		}
		if ($ab instanceof Y) {
			assertType('Bug6663\X', $xy);
			assertType('Bug6663\Y', $ab);
		}
	}
}

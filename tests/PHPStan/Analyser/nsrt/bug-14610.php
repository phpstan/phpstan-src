<?php

namespace Bug14610;

use function PHPStan\Testing\assertType;

function test(): void
{
	$value = 0;

	if (isset($_SESSION['test'])) {
		$value = rand(0,3);
		if ($value == 1) {
		}
	}

	assertType('int<0, 3>', $value);

	if ($value == 0) {
		assertType('array<mixed>', $_SESSION);
	}
}

<?php

namespace Bug9985;

use function PHPStan\Testing\assertType;

function (): void {
	$warnings = [];

	if (rand(0, 100) >= 1) {
		$warnings['a'] = true;
	}

	if (rand(0, 100) >= 2) {
		$warnings['b'] = true;
	} elseif (rand(0, 100) >= 3) {
		$warnings['c'] = true;
	}

	assertType('array{}|array{a?: true, b: true}|array{a?: true, c?: true}', $warnings);

	if (!empty($warnings)) {
		assertType('array{a?: true, b: true}|(array{a?: true, c?: true}&non-empty-array)', $warnings);
	}
};

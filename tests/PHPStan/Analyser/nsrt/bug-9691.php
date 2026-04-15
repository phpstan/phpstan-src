<?php

declare(strict_types = 1);

namespace Bug9691;

use function PHPStan\Testing\assertType;

function (): void {
	$issues = [];
	$previousValue = 1;

	for ($i = 0; $i < 2; $i++) {
		if ($previousValue === $i) {
			$issues[0] = 0;
		}
		$issues[1]['abc'] = 'def';

		assertType("array{abc: 'def'}", $issues[1]);

		$previousValue = $i;
	}
};

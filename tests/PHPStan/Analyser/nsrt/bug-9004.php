<?php

namespace Bug9004;

use function PHPStan\Testing\assertType;

$test = [];
foreach (['a', 'b', 'c'] as $letter) {
	if (rand(0, 1) == 0) {
		assertType("array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}}", $test);
		if (isset($test['hi']) && is_string($test['hi'])) {
			continue;
		}
		$test['hi'][] = 42;
	} else {
		$test['hi'] = 'hello';
	}
}

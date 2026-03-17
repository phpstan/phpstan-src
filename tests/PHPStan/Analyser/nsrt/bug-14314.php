<?php

declare(strict_types = 1);

namespace Bug14314;

use function PHPStan\Testing\assertType;

function () {
	preg_match('/^(.)$/', '', $matches) || preg_match('/^(.)(.)(.)$/', '', $matches);
	assertType('array{}|array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string}|array{non-falsy-string, non-empty-string}', $matches);
	if (count($matches) === 2) {
		assertType('array{non-falsy-string, non-empty-string}', $matches);
		return;
	}
	assertType('array{}|array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string}', $matches);
	if (count($matches) === 4) {
		assertType('array{non-falsy-string, non-empty-string, non-empty-string, non-empty-string}', $matches);
	}
};

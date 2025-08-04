<?php declare(strict_types = 1);

namespace Bug12447;

use function PHPStan\Testing\assertType;

assertType('mixed', $data);
$data[] = 1;
assertType('mixed', $data);

function (): void {
	assertType('*ERROR*', $data);
	$data[] = 1;
	assertType('array{1}', $data);
};

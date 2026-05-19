<?php declare(strict_types = 1);

namespace Bug7711;

use function PHPStan\Testing\assertType;

/** @return int[]|null */
function getData(): ?array {
	return rand(0,1) ? [1,3,5] : null;
}

function test(): void
{
	TEST:
	$data = getData();
	if (!$data) {
		goto TEST;
	}

	assertType('non-empty-array<int>', $data);
	foreach ($data as $item) {
		assertType('int', $item);
		echo $item;
	}
}

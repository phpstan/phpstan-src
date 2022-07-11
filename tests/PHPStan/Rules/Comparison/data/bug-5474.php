<?php declare(strict_types = 1);

namespace Bug5474;

/**
 * @phpstan-param array{test: int} $data
 * @return void
 */
function testData(array $data) {
	var_dump($data);
}

/**
 * @phpstan-return array{test: int}
 */
function returnData3() {
	return ['test' => 5];
}

function (): void {
	$data = ['test' => 1];
	$data2 = ['test' => 1];
	$data3 = ['test' => 5];

	if ($data !== $data2) {
		testData($data);
	}

	if ($data !== $data3) {
		testData($data);
	}

	if ($data !== returnData3()) {
		testData($data);
	}
};

<?php declare(strict_types = 1);

namespace Bug6000;

use function PHPStan\Testing\assertType;

function (): void {
	/** @var array{psr-4?: array<string, string|string[]>, classmap?: list<string>} $data */
	$data = [];

	foreach ($data as $key => $value) {
		assertType('array<int|string, array<string>|string>', $data[$key]);
		if ($key === 'classmap') {
			assertType('array<int, string>', $data[$key]);
			echo implode(', ', $value); // not working :(
			echo implode(', ', $data[$key]); // this works though?!
		}
	}
};

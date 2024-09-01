<?php

namespace Bug6006;

use function PHPStan\Testing\assertType;

function bug6006() {
	/** @var array<string, null|string> $data */
	$data = [
		'name' => 'John',
		'dob' => null,
	];

	$data = array_filter($data, fn(?string $input): bool => (bool)$input);

	assertType('array<string, non-falsy-string>', $data);
}



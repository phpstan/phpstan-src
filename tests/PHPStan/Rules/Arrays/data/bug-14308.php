<?php declare(strict_types = 1);

namespace Bug14308;

use RuntimeException;

function getUi(string $s1, string $s2, string $s3): string
{
	$available = array_keys(array_filter([
		'swagger' => $s1,
		'redoc' => $s2,
		'scalar' => $s3,
	]));

	if ([] === $available) {
		throw new RuntimeException('No documentation UI is enabled.');
	}

	return $available[0];
}

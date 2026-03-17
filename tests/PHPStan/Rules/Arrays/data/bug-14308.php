<?php declare(strict_types = 1);

namespace Bug14308;

use RuntimeException;
use function PHPStan\Testing\assertType;

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

	assertType("list{0: 'redoc'|'scalar'|'swagger', 1?: 'redoc'|'scalar', 2?: 'scalar'}", $available);

	return $available[0];
}

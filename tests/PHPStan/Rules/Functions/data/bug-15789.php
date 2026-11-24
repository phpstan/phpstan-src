<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15789ReturnType;

use Exception;

function getEnvTest(string $name): string {
	$result = getenv(name: $name);
	if ($result === false) {
		throw new Exception('Could not find env var');
	}
	return $result;
}

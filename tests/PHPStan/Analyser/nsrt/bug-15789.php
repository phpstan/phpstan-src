<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15789;

use Exception;
use function PHPStan\Testing\assertType;

function getEnvTest(string $name): string {
	$result = getenv(name: $name);
	assertType('string|false', $result);
	if ($result === false) {
		throw new Exception('Could not find env var');
	}
	return $result;
}

function namedArguments(?string $stringOrNull): void {
	assertType('string|false', getenv(name: 'FOO'));
	assertType('string|false', getenv('FOO', local_only: true));
	assertType('string|false', getenv(name: 'FOO', local_only: true));
	assertType('string|false', getenv(local_only: true, name: 'FOO'));
	assertType('array<string, string>', getenv(local_only: true));
	assertType('array<string, string>', getenv(name: null));
	assertType('array<string, string>|string|false', getenv(name: $stringOrNull));
}

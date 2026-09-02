<?php // lint >= 8.0

namespace PasswordGetInfo;

use function PHPStan\Testing\assertType;
use function password_get_info;

function knownShape(string $hash): void
{
	$info = password_get_info($hash);

	assertType('array{algo: string|null, algoName: string, options: array<string, mixed>}', $info);
	assertType('string|null', $info['algo']);
	assertType('string', $info['algoName']);
	assertType('array<string, mixed>', $info['options']);
}

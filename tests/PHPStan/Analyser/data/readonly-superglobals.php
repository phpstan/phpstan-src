<?php declare(strict_types=1);

namespace ReadonlySuperglobalsTypes;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

function defaultTypes(): void
{
	assertType('array<array<array<mixed>|string>|string>', $_GET);
	assertType('array<array<array<mixed>|string>|string>', $_POST);
	assertType('array<array<array<mixed>|string>|string>', $_REQUEST);
	assertType('array<array{name: array<mixed>, full_path?: array<mixed>, type: array<mixed>, tmp_name: array<mixed>, error: array<mixed>, size: array<mixed>}|array{name: string, full_path?: string, type: string, tmp_name: string, error: int<0, 8>, size: int<0, max>}>', $_FILES);
	assertType('array<string>', $_ENV);
	assertType('array<mixed>', $_SESSION);
	assertType('array<mixed>', $GLOBALS);

	assertNativeType('array<mixed>', $_GET);
	assertNativeType('array<mixed>', $_FILES);
}

function configuredTypesTakePrecedence(): void
{
	assertType('array<string, string>', $_COOKIE);
	assertType('array<string, mixed>', $_SERVER);
}

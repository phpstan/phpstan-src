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
	assertType('array{PHP_SELF: string, REQUEST_TIME: int, REQUEST_TIME_FLOAT: float, argc?: int<0, max>, argv?: list<string>, ...<string>}', $_SERVER);
	assertType('array<mixed>', $_SESSION);
	assertType('array<mixed>', $GLOBALS);

	assertNativeType('array<mixed>', $_GET);
	assertNativeType('array<mixed>', $_FILES);
	assertNativeType('array<mixed>', $_SERVER);
}

function serverOffsets(): void
{
	assertType('string|null', $_SERVER['REQUEST_URI'] ?? null);
	assertType('int<0, max>|null', $_SERVER['argc'] ?? null);
	assertType('list<string>|null', $_SERVER['argv'] ?? null);
	assertType('string', $_SERVER['PHP_SELF']);
	assertType('string|null', $_SERVER['SCRIPT_FILENAME'] ?? null);
	assertType('int', $_SERVER['REQUEST_TIME']);
	assertType('float', $_SERVER['REQUEST_TIME_FLOAT']);

	if (isset($_SERVER['HTTP_HOST'])) {
		assertType('string', $_SERVER['HTTP_HOST']);
	}
}

function configuredTypesTakePrecedence(): void
{
	assertType('array<string, string>', $_COOKIE);
	assertType('array<string, string>', $_ENV);
}

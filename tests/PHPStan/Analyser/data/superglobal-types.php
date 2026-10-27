<?php declare(strict_types=1);

namespace SuperglobalTypes;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

function configuredTypes(): void
{
	assertType('array<array<array<mixed>|string>|string>', $_GET);
	assertType('array<string, string>', $_COOKIE);
	assertType('array<mixed>', $_POST);
	assertType('array<mixed>', $_SERVER);
	assertType('array<mixed>', $_FILES);
	assertType('array<mixed>', $_ENV);

	assertNativeType('array<mixed>', $_GET);
	assertNativeType('array<mixed>', $_COOKIE);
}

function offsets(): void
{
	assertType('array<array<mixed>|string>|string', $_GET['id']);
	assertType('array<array<mixed>|string>|string|null', $_GET['id'] ?? null);
	assertNativeType('mixed', $_GET['id']);

	if (isset($_GET['id'])) {
		assertType('array<array<mixed>|string>|string', $_GET['id']);
	}

	if (isset($_GET['id']) && is_string($_GET['id'])) {
		assertType('string', $_GET['id']);
	}

	assertType('string|null', $_COOKIE['session'] ?? null);
}

function writes(): void
{
	$_GET['id'] = 1;
	assertType('1', $_GET['id']);

	$_GET = [];
	assertType('array{}', $_GET);
}

function invalidatedByCalls(): void
{
	$_GET['id'] = 1;
	assertType('1', $_GET['id']);
	doSomething();
	assertType('array<array<array<mixed>|string>|string>', $_GET);
}

function doSomething(): void
{
}

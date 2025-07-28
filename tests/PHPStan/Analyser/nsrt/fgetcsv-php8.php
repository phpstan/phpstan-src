<?php // lint >= 8.0

declare(strict_types = 1);

namespace TestFGetCsvPhp8;

use function PHPStan\Testing\assertType;

function test($resource): void
{
	assertType('non-empty-list<string|null>|false', fgetcsv($resource));
}

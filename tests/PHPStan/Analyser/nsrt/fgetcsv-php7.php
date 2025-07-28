<?php // lint < 8.0

declare(strict_types = 1);

namespace TestFGetCsvPhp7;

use function PHPStan\Testing\assertType;

function test($resource): void
{
	assertType('non-empty-list<string|null>|false|null', fgetcsv($resource)); // nullable when invalid argument is given (https://3v4l.org/4WmR5#v7.4.30)
}

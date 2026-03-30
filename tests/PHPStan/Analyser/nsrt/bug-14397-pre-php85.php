<?php // lint < 8.5

declare(strict_types = 1);

namespace Bug14397PrePhp85;

use function filter_var;
use function PHPStan\Testing\assertType;

function test(mixed $mixed): void
{
	// On PHP < 8.5, FILTER_THROW_ON_FAILURE doesn't truly exist
	// so it shouldn't remove false from the return type
	assertType('int|false', filter_var($mixed, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE));
	assertType('int|false', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_THROW_ON_FAILURE]));
}

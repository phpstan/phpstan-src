<?php declare(strict_types = 1);

namespace Bug14736;

use function PHPStan\Testing\assertType;

function test(): void
{
	assertType('string|false', xdebug_get_profiler_filename());
	assertType('string|false', xdebug_get_gcstats_filename());
	assertType('string|false', xdebug_get_tracefile_name());
	assertType('string|null', xdebug_start_trace());
	assertType('string|false', xdebug_stop_trace());
	assertType('string|false', xdebug_start_gcstats());
	assertType('string|false', xdebug_stop_gcstats());
}

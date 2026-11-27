<?php declare(strict_types = 1);

namespace Bug15849;

use function PHPStan\Testing\assertType;

function isFinished(): bool
{
	return random_int(0, 1) === 1;
}

/** Returns once isFinished() was true in two consecutive polls. */
function waitStable(): void
{
	$c = 0;
	while (true) {
		if (isFinished()) {
			if (++$c >= 2) {
				return;
			}
		} else {
			assertType('0|1', $c); // 0|1 at runtime: true, then false
			$c = 0;
		}
	}
}

/** @param list<string> $events */
function hasLineWithoutPath(array $events, ?string $path): bool
{
	$line = null;
	foreach ($events as $event) {
		if ($event === 'clear-path') {
			$path = null;
			continue;
		}

		if ($event === 'set-line') {
			$line = $path !== null ? 1 : null;
			continue;
		}

		if ($line === null) {
			continue;
		}

		assertType('string|null', $path);
		if ($path === null) {
			return true;
		}
	}

	return false;
}

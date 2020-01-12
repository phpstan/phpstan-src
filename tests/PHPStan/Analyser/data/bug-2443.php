<?php

namespace Analyser\Bug2443;

use function PHPStan\Analyser\assertType;

/**
 * @param array<int,mixed> $a
 */
function (array $a): void
{
	assertType('bool', array_filter($a) !== []);
	assertType('bool', [] !== array_filter($a));

	assertType('bool', array_filter($a) === []);
	assertType('bool', [] === array_filter($a));
};

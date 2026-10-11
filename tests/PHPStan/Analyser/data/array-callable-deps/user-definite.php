<?php declare(strict_types = 1);

namespace ArrayCallableDeps;

/**
 * A definite callable array: the file depends on what calling it returns.
 */
function definite(): void
{
	$cb = [Factory::class, 'create'];
}

<?php declare(strict_types = 1);

namespace Bug7984;

/**
 * @template TReturn
 * @param  \Closure(): TReturn  $closure
 * @return TReturn
 */
function critical(\Closure $closure): mixed
{
	return $closure();
}

function inc(int $incr = 1): int|false
{
	return critical(function () use ($incr): int|false {
		return rand(0, 1) ? $incr: false;
	});
}

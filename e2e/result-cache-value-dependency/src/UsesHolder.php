<?php

namespace ResultCacheE2EValueDependency;

function usesHolder(Holder $holder): int
{
	$read = \Closure::bind(static fn (Holder $holder) => $holder->debug, null, Holder::class);

	return $read($holder);
}

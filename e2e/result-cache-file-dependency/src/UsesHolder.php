<?php

namespace ResultCacheE2EFileDependency;

function usesHolder(Holder $holder): int
{
	$read = \Closure::bind(static fn (Holder $holder) => $holder->value, null, Holder::class);

	return $read($holder);
}

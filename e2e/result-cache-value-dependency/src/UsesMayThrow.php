<?php

namespace ResultCacheE2EValueDependency;

function usesMayThrow(): void
{
	try {
		mayThrow();
	} catch (\RuntimeException $e) {
	}
}

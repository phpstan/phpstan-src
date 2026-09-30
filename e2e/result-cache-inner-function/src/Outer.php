<?php

namespace ResultCacheE2EInnerFunction;

function outer(string $s): string
{
	if (!function_exists('ResultCacheE2EInnerFunction\inner')) {
		function inner(string $s): string
		{
			return strtolower($s);
		}
	}

	return inner($s);
}

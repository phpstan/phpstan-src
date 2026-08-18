<?php declare(strict_types = 1);

namespace ShadowedNativeFunctionE2e;

function doFoo(string $s): bool
{
	return str_contains($s, 'x')
		|| str_starts_with($s, 'y')
		|| str_ends_with(haystack: $s, needle: 'z');
}

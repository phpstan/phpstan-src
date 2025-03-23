<?php

namespace Bug8555;

/**
 * @return array<string, int|null>
 */
function test(int $first, int $second): array
{
	return [
		'test' => $first && $second ? $first : null,
		'test2' => $first && $second ? $first : null,
	];
}

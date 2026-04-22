<?php declare(strict_types = 1);

namespace Bug7759;

function take_string(string $in): void {}

/**
 * @param array{foo?: array{bar?: string}}|array<string, string> $in
 */
function check(array $in): void
{
	if (array_key_exists('test123', $in)) {
		take_string($in['test123']);
	}
}

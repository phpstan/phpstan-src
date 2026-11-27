<?php declare(strict_types = 1);

namespace Bug15856;

/** @param list<string> $matches */
function takeList(array $matches): void {}

function f(string $content): void
{
	if (preg_match_all('/abc[^#]*#/', $content, $matches)) {
		takeList($matches[0]);
	}

	if (preg_match_all('/abc/', $content, $m2) > 0) {
		takeList($m2[0]);
	}
}

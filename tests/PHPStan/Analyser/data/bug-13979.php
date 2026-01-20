<?php

namespace Bug13979;

/**
 * @param array<string, mixed> $bar
 * @param-out array<string, mixed>|null $bar
 */
function foo(array &$bar): void {
	if ($bar === []) {
		$bar = null;
	}
}

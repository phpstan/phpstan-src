<?php

namespace Bug7491;

function test(string $text = ''): bool {
	$world = preg_match('/\b(world)$/i', $text, $matches) === false ? '' : ($matches[1] ?? '');
	if ($world == '') {
		echo "Not found\n";
		return false;
	} else {
		echo "Found '$world'\n";
		return true;
	}
}

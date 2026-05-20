<?php

namespace Bug11494;

/**
 * @param array{short: string}|array{long: string, details: string} $a
 */
function test(array $a): void {
	if (\count($a) === 2) {
		if (isset($a['short'])) {
			var_dump('reached');
		}

		var_dump($a['details']);
	}
}

test(['short' => 'thing', 'extra' => 'other']);

<?php

/**
 * @param int[] $keys
 * @param array<int, int|null> $things
 */
function evaluateThings(array $keys, array $things): void
{
	foreach ($keys as $key) {
		if (array_key_exists($key, $things) && $things[$key] === null) {
			echo "Value for key $key is null\n";
			continue;
		}

		if (isset($things[$key])) {
			echo "Key $key is set\n";
		}
	}
}

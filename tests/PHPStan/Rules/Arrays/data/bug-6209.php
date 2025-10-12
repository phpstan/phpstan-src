<?php

namespace Bug6209;

/**
 * @param mixed[] $values
 * @return mixed[]
 */
function apply(array $values): array
{
	if (!array_key_exists('key', $values)) {
		$values['key'][] = 'any';
	}

	return $values;
}

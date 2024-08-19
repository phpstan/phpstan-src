<?php

namespace Bug11518;

/**
 * @param mixed[] $a
 * @return array{thing: mixed}
 * */
function blah(array $a): array
{
	if (!array_key_exists('thing', $a)) {
		$a['thing'] = 'bla';
	}

	return $a;
}

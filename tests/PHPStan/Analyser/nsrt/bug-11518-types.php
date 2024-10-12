<?php

namespace Bug11518Types;

use function PHPStan\Testing\assertType;

/**
 * @param mixed[] $a
 * @return array{thing: mixed}
 * */
function blah(array $a): array
{
	if (!array_key_exists('thing', $a)) {
		$a['thing'] = 'bla';
		assertType('non-empty-array&hasOffsetValue(\'thing\', \'bla\')', $a);
	} else {
		assertType('non-empty-array&hasOffset(\'thing\')', $a);
	}

	assertType('non-empty-array&hasOffsetValue(\'thing\', mixed)', $a);

	return $a;
}

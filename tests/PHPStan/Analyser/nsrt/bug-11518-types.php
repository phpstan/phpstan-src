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
		assertType('hasOffsetValue(\'thing\', \'bla\')&non-empty-array', $a);
	} else {
		assertType('array&hasOffset(\'thing\')', $a);
	}

	assertType('array&hasOffsetValue(\'thing\', mixed)', $a);

	return $a;
}

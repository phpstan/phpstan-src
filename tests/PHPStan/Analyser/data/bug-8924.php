<?php

namespace Bug8924;

use function PHPStan\Testing\assertType;

/**
 * @param list<int> $array
 */
function foo(array $array): void {
	foreach ($array as $element) {
		assertType('int', $element);
		$array = null;
	}
}

function makeValidNumbers(): array
{
	$validNumbers = [1, 2];
	foreach ($validNumbers as $k => $v) {
		assertType("non-empty-list<-2|-1|1|2|' 1'|' 2'>", $validNumbers);
		assertType('0|1', $k);
		assertType('1|2', $v);
		$validNumbers[] = -$v;
		$validNumbers[] = ' ' . (string)$v;
		assertType("non-empty-list<-2|-1|1|2|' 1'|' 2'>", $validNumbers);
	}

	return $validNumbers;
}

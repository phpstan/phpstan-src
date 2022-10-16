<?php

namespace ArrayFlip;

use function PHPStan\Testing\assertType;

/**
 * @param int[] $integerList
 */
function foo($integerList)
{
	$flip = array_flip($integerList);
	assertType('array<int, (int|string)>', $flip);
}

/**
 * @param mixed[] $list
 */
function foo3($list)
{
	$flip = array_flip($list);

	assertType('array<int|string, (int|string)>', $flip);
}

/**
 * @param array<int, 1|2|3> $array
 */
function foo4($array)
{
	$flip = array_flip($array);
	assertType('array<1|2|3, int>', $flip);
}


/**
 * @param array<1|2|3, string> $array
 */
function foo5($array)
{
	$flip = array_flip($array);
	assertType('array<string, 1|2|3>', $flip);
}

/**
 * @param non-empty-array<1|2|3, 4|5|6> $array
 */
function foo6($array)
{
	$flip = array_flip($array);
	assertType('non-empty-array<4|5|6, 1|2|3>', $flip);
}

/**
 * @param list<1|2|3> $array
 */
function foo7($array)
{
	$flip = array_flip($array);
	assertType('array<1|2|3, int<0, max>>', $flip);
}

function foo8($mixed)
{
	assertType('mixed', $mixed);
	$mixed = array_flip($mixed);
	assertType('array', $mixed);
}

function foo9($mixed)
{
	if(is_array($mixed)) {
		assertType('array<int|string, (int|string)>', array_flip($mixed));
	} else {
		assertType('mixed~array', $mixed);
		assertType('*ERROR*', array_flip($mixed));
	}
}

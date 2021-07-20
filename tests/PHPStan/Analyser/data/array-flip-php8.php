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

	assertType('array', $flip);
}

/**
 * @param array<int, 1|2|3> $list
 */
function foo4($array)
{
	$flip = array_flip($array);
	assertType('array<1|2|3, int>', $flip);
}


/**
 * @param array<1|2|3, string> $list
 */
function foo5($array)
{
	$flip = array_flip($array);
	assertType('array<string, 1|2|3>', $flip);
}

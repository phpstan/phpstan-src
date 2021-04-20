<?php

namespace ArraySum;

use function PHPStan\Testing\assertType;

/**
 * @param int[] $integerList
 */
function foo($integerList)
{
	$sum = array_sum($integerList);
	assertType('int', $sum);
}

/**
 * @param float[] $floatList
 */
function foo2($floatList)
{
	$sum = array_sum($floatList);
	assertType('0|float', $sum);
}

/**
 * @param non-empty-array<float> $floatList
 */
function foo3($floatList)
{
	$sum = array_sum($floatList);
	assertType('float', $sum);
}

/**
 * @param mixed[] $list
 */
function foo4($list)
{
	$sum = array_sum($list);
	assertType('float|int', $sum);
}

/**
 * @param string[] $list
 */
function foo5($list)
{
	$sum = array_sum($list);
	assertType('float|int', $sum);
}

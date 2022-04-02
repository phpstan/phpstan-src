<?php

namespace ConstantPhpdocType;

use const PREG_SPLIT_NO_EMPTY as PREG_SPLIT_NO_EMPTY_ALIAS;
use function PHPStan\Testing\assertType;

const MY_CONST = 100;
const ANOTHER_CONST = 200;
const PREG_SPLIT_NO_EMPTY_COPY = PREG_SPLIT_NO_EMPTY;

const BAR = 10;
class BAR {}

/**
 * @param MY_CONST $one
 * @param MY_CONST|ANOTHER_CONST $two
 * @param PREG_SPLIT_NO_EMPTY $three
 * @param PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE $four
 * @param PREG_SPLIT_NO_EMPTY_COPY $five
 * @param PREG_SPLIT_NO_EMPTY_ALIAS $six
 * @param BAR $seven
 */
function foo(
	$one,
	$two,
	$three,
	$four,
	$five,
	$six,
	$seven
) {
	assertType('100', $one);
	assertType('100|200', $two);
	assertType('1', $three);
	assertType('1|2', $four);
	assertType('1', $five);
	assertType('ConstantPhpdocType\PREG_SPLIT_NO_EMPTY_ALIAS', $six); // use const not supported
	assertType('ConstantPhpdocType\BAR', $seven); // classes take precedence over constants
}

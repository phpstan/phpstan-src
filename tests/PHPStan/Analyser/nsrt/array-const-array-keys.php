<?php

use function PHPStan\Testing\assertType;

/**
 * @param int<1, 3> $oneTwoThree
 * @param 2|'aA' $constUnion
 */
function doFoo(array $arr, $oneTwoThree, $constUnion) {
	if ($arr[$oneTwoThree]) {
		assertType("non-empty-array&hasOffsetValue(1, mixed~(0|0.0|''|'0'|array{}|false|null))&hasOffsetValue(2, mixed~(0|0.0|''|'0'|array{}|false|null))&hasOffsetValue(3, mixed~(0|0.0|''|'0'|array{}|false|null))", $arr);
	}

	if ($arr[$constUnion]) {
		assertType("non-empty-array&hasOffsetValue('aA', mixed~(0|0.0|''|'0'|array{}|false|null))&hasOffsetValue(1, mixed)&hasOffsetValue(2, mixed~(0|0.0|''|'0'|array{}|false|null))&hasOffsetValue(3, mixed)", $arr);
	}
}

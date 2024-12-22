<?php

declare(strict_types=1);

namespace LooseSemantics;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayTrue(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('true', $true == $true);
		assertType('false', $true == $false);
		assertType('true', $true == $one);
		assertType('false', $true == $zero);
		assertType('true', $true == $minusOne);
		assertType('true', $true == $oneStr);
		assertType('false', $true == $zeroStr);
		assertType('true', $true == $minusOneStr);
		assertType('true', $true == $plusOneStr);
		assertType('false', $true == $null);
		assertType('false', $true == $emptyArr);
		assertType('true', $true == $phpStr);
		assertType('false', $true == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayFalse(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('false', $false == $true);
		assertType('true', $false == $false);
		assertType('false', $false == $one);
		assertType('true', $false == $zero);
		assertType('false', $false == $minusOne);
		assertType('false', $false == $oneStr);
		assertType('true', $false == $zeroStr);
		assertType('false', $false == $minusOneStr);
		assertType('false', $false == $plusOneStr);
		assertType('true', $false == $null);
		assertType('true', $false == $emptyArr);
		assertType('false', $false == $phpStr);
		assertType('true', $false == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayOne(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('true', $one == $true);
		assertType('false', $one == $false);
		assertType('true', $one == $one);
		assertType('false', $one == $zero);
		assertType('false', $one == $minusOne);
		assertType('true', $one == $oneStr);
		assertType('false', $one == $zeroStr);
		assertType('false', $one == $minusOneStr);
		assertType('true', $one == $plusOneStr);
		assertType('false', $one == $null);
		assertType('false', $one == $emptyArr);
		assertType('false', $one == $phpStr);
		assertType('false', $one == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayZero(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('false', $zero == $true);
		assertType('true', $zero == $false);
		assertType('false', $zero == $one);
		assertType('true', $zero == $zero);
		assertType('false', $zero == $minusOne);
		assertType('false', $zero == $oneStr);
		assertType('true', $zero == $zeroStr);
		assertType('false', $zero == $minusOneStr);
		assertType('false', $zero == $plusOneStr);
		assertType('true', $zero == $null);
		assertType('false', $zero == $emptyArr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayMinusOne(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('true', $minusOne == $true);
		assertType('false', $minusOne == $false);
		assertType('false', $minusOne == $one);
		assertType('false', $minusOne == $zero);
		assertType('true', $minusOne == $minusOne);
		assertType('false', $minusOne == $oneStr);
		assertType('false', $minusOne == $zeroStr);
		assertType('true', $minusOne == $minusOneStr);
		assertType('false', $minusOne == $plusOneStr);
		assertType('false', $minusOne == $null);
		assertType('false', $minusOne == $emptyArr);
		assertType('false', $minusOne == $phpStr);
		assertType('false', $minusOne == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayOneStr(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('true', $oneStr == $true);
		assertType('false', $oneStr == $false);
		assertType('true', $oneStr == $one);
		assertType('false', $oneStr == $zero);
		assertType('false', $oneStr == $minusOne);
		assertType('true', $oneStr == $oneStr);
		assertType('false', $oneStr == $zeroStr);
		assertType('false', $oneStr == $minusOneStr);
		assertType('true', $oneStr == $plusOneStr);
		assertType('false', $oneStr == $null);
		assertType('false', $oneStr == $emptyArr);
		assertType('false', $oneStr == $phpStr);
		assertType('false', $oneStr == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayZeroStr(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('false', $zeroStr == $true);
		assertType('true', $zeroStr == $false);
		assertType('false', $zeroStr == $one);
		assertType('true', $zeroStr == $zero);
		assertType('false', $zeroStr == $minusOne);
		assertType('false', $zeroStr == $oneStr);
		assertType('true', $zeroStr == $zeroStr);
		assertType('false', $zeroStr == $minusOneStr);
		assertType('false', $zeroStr == $plusOneStr);
		assertType('false', $zeroStr == $null);
		assertType('false', $zeroStr == $emptyArr);
		assertType('false', $zeroStr == $phpStr);
		assertType('false', $zeroStr == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayMinusOneStr(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('true', $minusOneStr == $true);
		assertType('false', $minusOneStr == $false);
		assertType('false', $minusOneStr == $one);
		assertType('false', $minusOneStr == $zero);
		assertType('true', $minusOneStr == $minusOne);
		assertType('false', $minusOneStr == $oneStr);
		assertType('false', $minusOneStr == $zeroStr);
		assertType('true', $minusOneStr == $minusOneStr);
		assertType('false', $minusOneStr == $plusOneStr);
		assertType('false', $minusOneStr == $null);
		assertType('false', $minusOneStr == $emptyArr);
		assertType('false', $minusOneStr == $phpStr);
		assertType('false', $minusOneStr == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayPlusOneStr(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('true', $plusOneStr == $true);
		assertType('false', $plusOneStr == $false);
		assertType('true', $plusOneStr == $one);
		assertType('false', $plusOneStr == $zero);
		assertType('false', $plusOneStr == $minusOne);
		assertType('true', $plusOneStr == $oneStr);
		assertType('false', $plusOneStr == $zeroStr);
		assertType('false', $plusOneStr == $minusOneStr);
		assertType('true', $plusOneStr == $plusOneStr);
		assertType('false', $plusOneStr == $null);
		assertType('false', $plusOneStr == $emptyArr);
		assertType('false', $plusOneStr == $phpStr);
		assertType('false', $plusOneStr == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayNull(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('false', $null == $true);
		assertType('true', $null == $false);
		assertType('false', $null == $one);
		assertType('true', $null == $zero);
		assertType('false', $null == $minusOne);
		assertType('false', $null == $oneStr);
		assertType('false', $null == $zeroStr);
		assertType('false', $null == $minusOneStr);
		assertType('false', $null == $plusOneStr);
		assertType('true', $null == $null);
		assertType('true', $null == $emptyArr);
		assertType('false', $null == $phpStr);
		assertType('true', $null == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayEmptyArray(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('false', $emptyArr == $true);
		assertType('true', $emptyArr == $false);
		assertType('false', $emptyArr == $one);
		assertType('false', $emptyArr == $zero);
		assertType('false', $emptyArr == $minusOne);
		assertType('false', $emptyArr == $oneStr);
		assertType('false', $emptyArr == $zeroStr);
		assertType('false', $emptyArr == $minusOneStr);
		assertType('false', $emptyArr == $plusOneStr);
		assertType('true', $emptyArr == $null);
		assertType('true', $emptyArr == $emptyArr);
		assertType('false', $emptyArr == $phpStr);
		assertType('false', $emptyArr == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayNonFalsyStr(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('true', $phpStr == $true);
		assertType('false', $phpStr == $false);
		assertType('false', $phpStr == $one);
		assertType('false', $phpStr == $minusOne);
		assertType('false', $phpStr == $oneStr);
		assertType('false', $phpStr == $zeroStr);
		assertType('false', $phpStr == $minusOneStr);
		assertType('false', $phpStr == $plusOneStr);
		assertType('false', $phpStr == $null);
		assertType('false', $phpStr == $emptyArr);
		assertType('true', $phpStr == $phpStr);
		assertType('false', $phpStr == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayEmptyStr(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('false', $emptyStr == $true);
		assertType('true', $emptyStr == $false);
		assertType('false', $emptyStr == $one);
		assertType('false', $emptyStr == $minusOne);
		assertType('false', $emptyStr == $oneStr);
		assertType('false', $emptyStr == $zeroStr);
		assertType('false', $emptyStr == $minusOneStr);
		assertType('false', $emptyStr == $plusOneStr);
		assertType('true', $emptyStr == $null);
		assertType('false', $emptyStr == $emptyArr);
		assertType('false', $emptyStr == $phpStr);
		assertType('true', $emptyStr == $emptyStr);
	}

	/**
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param '+1' $plusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param int<10, 20> $intRange
	 */
	public function sayInt(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$plusOneStr,
		$null,
		$emptyArr,
		array $array,
		int $int,
		int $intRange,
	): void
	{
		assertType('bool', $int == $true);
		assertType('bool', $int == $false);
		assertType('bool', $int == $one);
		assertType('bool', $int == $zero);
		assertType('bool', $int == $minusOne);
		assertType('bool', $int == $oneStr);
		assertType('bool', $int == $zeroStr);
		assertType('bool', $int == $minusOneStr);
		assertType('bool', $int == $plusOneStr);
		assertType('bool', $int == $null);
		assertType('false', $int == $emptyArr);
		assertType('false', $int == $array);

		assertType('false', $intRange == $emptyArr);
		assertType('false', $intRange == $array);

	}

	/**
	 * @param true|1|"1" $looseOne
	 * @param false|0|"0" $looseZero
	 * @param false|1 $constMix
	 */
	public function sayConstUnion(
		$looseOne,
		$looseZero,
		$constMix
	): void
	{
		assertType('true', $looseOne == 1);
		assertType('false', $looseOne == 0);
		assertType('true', $looseOne == true);
		assertType('false', $looseOne == false);
		assertType('true', $looseOne == "1");
		assertType('false', $looseOne == "0");
		assertType('false', $looseOne == []);

		assertType('false', $looseZero == 1);
		assertType('true', $looseZero == 0);
		assertType('false', $looseZero == true);
		assertType('true', $looseZero == false);
		assertType('false', $looseZero == "1");
		assertType('true', $looseZero == "0");
		assertType('bool', $looseZero == []);

		assertType('bool', $constMix == 0);
		assertType('bool', $constMix == 1);
		assertType('bool', $constMix == true);
		assertType('bool', $constMix == false);
		assertType('bool', $constMix == "1");
		assertType('bool', $constMix == "0");
		assertType('bool', $constMix == []);

		assertType('true', $looseOne == $looseOne);
		assertType('true', $looseZero == $looseZero);
		assertType('false', $looseOne == $looseZero);
		assertType('false', $looseZero == $looseOne);
		assertType('bool', $looseOne == $constMix);
		assertType('bool', $constMix == $looseOne);
		assertType('bool', $looseZero == $constMix);
		assertType('bool', $constMix == $looseZero);
	}

	/**
	 * @param uppercase-string $upper
	 * @param lowercase-string $lower
	 * @param array{} $emptyArr
	 * @param non-empty-array $nonEmptyArr
	 * @param int<10, 20> $intRange
	 */
	public function sayIntersection(
		string $upper,
		string $lower,
		string $s,
		array $emptyArr,
		array $nonEmptyArr,
		array $arr,
		int $i,
		int $intRange,
	): void
	{
		// https://3v4l.org/q8OP2
		assertType('true', '1e2' == '1E2');
		assertType('false', '1e2' === '1E2');

		assertType('bool', '' == $upper);
		assertType('bool', '0' == $upper);
		assertType('false', 'a' == $upper);
		assertType('false', 'abc' == $upper);
		assertType('false', 'aBc' == $upper);
		assertType('bool', '1e2' == $upper);
		assertType('bool', strtoupper($s) == $upper);
		assertType('bool', strtolower($s) == $upper);
		assertType('bool', $upper == $lower);

		assertType('bool', '0' == $lower);
		assertType('false', 'A' == $lower);
		assertType('false', 'ABC' == $lower);
		assertType('false', 'AbC' == $lower);
		assertType('bool', '1E2' == $lower);
		assertType('bool', strtoupper($s) == $lower);
		assertType('bool', strtolower($s) == $lower);
		assertType('bool', $lower == $upper);

		assertType('false', $arr == $i);
		assertType('false', $nonEmptyArr == $i);
		assertType('false', $arr == $intRange);
		assertType('false', $nonEmptyArr == $intRange);
		assertType('bool', $emptyArr == $nonEmptyArr); // should be false
		assertType('false', $nonEmptyArr == $emptyArr);
		assertType('bool', $arr == $nonEmptyArr);
		assertType('bool', $nonEmptyArr == $arr);

		assertType('bool', '' == $lower);
		if ($lower != '') {
			assertType('false', '' == $lower);
		}
		if ($upper != '') {
			assertType('false', '' == $upper);
		}
	}

}

<?php

declare(strict_types=1);

namespace LooseSemantics;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param non-empty-string $nonEmptyString
	 * @param non-empty-array $nonEmptyArray
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param 'a'|'123'|'123.23' $unionMaybeNumeric
	 * @param 1|2|3 $unionNumbers
	 * @param 'a'|'b'|'c' $unionStrings
	 * @param 'a'|'123'|123|array $unionMaybeArray
	 */
	public function sayTrue(
		$nonEmptyString,
		$nonEmptyArray,
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj,
		$unionMaybeNumeric,
		$unionNumbers,
		$unionStrings,
		$unionMaybeArray
	): void
	{
		assertType('true', $true == $nonEmptyString);
		assertType('true', $true == $nonEmptyArray);
		assertType('true', $true == $true);
		assertType('false', $true == $false);
		assertType('true', $true == $one);
		assertType('false', $true == $zero);
		assertType('true', $true == $minusOne);
		assertType('true', $true == $oneStr);
		assertType('false', $true == $zeroStr);
		assertType('true', $true == $minusOneStr);
		assertType('false', $true == $null);
		assertType('false', $true == $emptyArr);
		assertType('true', $true == $phpStr);
		assertType('false', $true == $emptyStr);
		assertType('bool', $true == $arr);
		assertType('bool', $true == $int);
		assertType('bool', $true == $float);
		assertType('bool', $true == $bool);
		assertType('bool', $true == $string);
		assertType('true', $true == $obj);
		assertType('true', $true == new \stdClass());
		assertType('true', $true == $unionMaybeNumeric);
		assertType('true', $true == $unionNumbers);
		assertType('true', $true == $unionStrings);
		assertType('bool', $true == $unionMaybeArray);
	}

	/**
	 * @param non-empty-string $nonEmptyString
	 * @param non-empty-array $nonEmptyArray
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param array $arr
	 * @param 'a'|'123'|'123.23' $unionMaybeNumeric
	 * @param 1|2|3 $unionNumbers
	 * @param 'a'|'b'|'c' $unionStrings
	 * @param 'a'|'123'|123|array $unionMaybeArray
	 */
	public function sayFalse(
		$nonEmptyString,
		$nonEmptyArray,
		$callable,
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj,
		$unionMaybeNumeric,
		$unionNumbers,
		$unionStrings,
		$unionMaybeArray
	): void
	{
		assertType('false', $false == $nonEmptyString);
		assertType('false', $false == $nonEmptyArray);
		assertType('false', $false == $true);
		assertType('true', $false == $false);
		assertType('false', $false == $one);
		assertType('true', $false == $zero);
		assertType('false', $false == $minusOne);
		assertType('false', $false == $oneStr);
		assertType('true', $false == $zeroStr);
		assertType('false', $false == $minusOneStr);
		assertType('true', $false == $null);
		assertType('true', $false == $emptyArr);
		assertType('false', $false == $phpStr);
		assertType('true', $false == $emptyStr);
		assertType('bool', $false == $arr);
		assertType('bool', $false == $int);
		assertType('bool', $false == $float);
		assertType('bool', $false == $bool);
		assertType('bool', $false == $string);
		assertType('false', $false == $obj);
		assertType('false', $false == new \stdClass());
		assertType('false', $false == $unionMaybeNumeric);
		assertType('false', $false == $unionNumbers);
		assertType('false', $false == $unionStrings);
		assertType('bool', $false == $unionMaybeArray);
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
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj
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
		assertType('false', $one == $null);
		assertType('false', $one == $emptyArr);
		assertType('false', $one == $phpStr);
		assertType('false', $one == $emptyStr);
		assertType('false', $one == $arr);
		assertType('bool', $one == $int);
		assertType('bool', $one == $float);
		assertType('bool', $one == $bool);
		assertType('bool', $one == $string);
		assertType('true', $one == $obj);
		assertType('true', $one == new \stdClass());
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
		assertType('false', $oneStr == $null);
		assertType('false', $oneStr == $emptyArr);
		assertType('false', $oneStr == $phpStr);
		assertType('false', $oneStr == $emptyStr);
		assertType('false', $oneStr == new \stdClass());
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
		assertType('false', $zeroStr == $null);
		assertType('false', $zeroStr == $emptyArr);
		assertType('false', $zeroStr == $phpStr);
		assertType('false', $zeroStr == $emptyStr);
		assertType('false', $zeroStr == new \stdClass());
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
		assertType('false', $minusOneStr == $null);
		assertType('false', $minusOneStr == $emptyArr);
		assertType('false', $minusOneStr == $phpStr);
		assertType('false', $minusOneStr == $emptyStr);
		assertType('false', $minusOneStr == new \stdClass());
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
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
	): void
	{
		assertType('true', $phpStr == $true);
		assertType('false', $phpStr == $false);
		assertType('false', $phpStr == $one);
		assertType('false', $phpStr == $minusOne);
		assertType('false', $phpStr == $oneStr);
		assertType('false', $phpStr == $zeroStr);
		assertType('false', $phpStr == $minusOneStr);
		assertType('false', $phpStr == $null);
		assertType('false', $phpStr == $emptyArr);
		assertType('true', $phpStr == $phpStr);
		assertType('false', $phpStr == $emptyStr);
		assertType('false', $phpStr == new \stdClass());
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
		assertType('true', $emptyStr == $null);
		assertType('false', $emptyStr == $emptyArr);
		assertType('false', $emptyStr == $phpStr);
		assertType('true', $emptyStr == $emptyStr);
		assertType('false', $emptyStr == new \stdClass());
	}

	/**
	 * @param null $null
	 * @param 0|false $truthyLooseNullUnion
	 * @param true|1 $falseyLooseNullUnion
	 * @param 0|false|1 $undecidedLooseNullUnion
	 */
	public function looseUnion(
		$null,
		$truthyLooseNullUnion,
		$falseyLooseNullUnion,
		$undecidedLooseNullUnion
	) {
		assertType('true', $null == $truthyLooseNullUnion);
		assertType('false', $null == $falseyLooseNullUnion);
		assertType('bool', $null == $undecidedLooseNullUnion);
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
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param object $obj
	 *
	 * https://3v4l.org/JXskZ
	 */
	public function looseObj(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj
	) {
		assertType('true', $obj == $true);
		assertType('false', $obj == $false);
		assertType('false', $obj == $zero);
		assertType('true', $obj == $one);
		assertType('false', $obj == 10);
		assertType('false', $obj == $minusOne);
		assertType('false', $obj == $oneStr);
		assertType('false', $obj == $zeroStr);
		assertType('false', $obj == $minusOneStr);
		assertType('false', $obj == $null);
		assertType('false', $obj == $emptyArr);
		assertType('false', $obj == $phpStr);
		assertType('false', $obj == $emptyStr);
		assertType('bool', $obj == $arr);
		assertType('bool', $obj == $int);
		assertType('false', $obj == $float);
		assertType('bool', $obj == $bool);
		assertType('false', $obj == $string);
		assertType('true', $obj == $obj);
		assertType('bool', $obj == new \stdClass());
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
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param HelloWorld $obj
	 *
	 * https://3v4l.org/JXskZ
	 */
	public function looseNamedObj(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj
	) {
		assertType('true', $obj == $true);
		assertType('false', $obj == $false);
		assertType('false', $obj == $zero);
		assertType('true', $obj == $one);
		assertType('false', $obj == 10);
		assertType('false', $obj == $minusOne);
		assertType('false', $obj == $oneStr);
		assertType('false', $obj == $zeroStr);
		assertType('false', $obj == $minusOneStr);
		assertType('false', $obj == $null);
		assertType('false', $obj == $emptyArr);
		assertType('false', $obj == $phpStr);
		assertType('false', $obj == $emptyStr);
		assertType('bool', $obj == $arr);
		assertType('bool', $obj == $int);
		assertType('false', $obj == $float);
		assertType('bool', $obj == $bool);
		assertType('false', $obj == $string);
		assertType('true', $obj == $obj);
		assertType('bool', $obj == new \stdClass());
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
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param array $arr
	 * @param 'a'|'123'|'123.23' $unionMaybeNumeric
	 * @param 1|2|3 $unionNumbers
	 * @param 'a'|'b'|'c' $unionStrings
	 *
	 * https://3v4l.org/CMCUB
	 * https://3v4l.org/vT28m
	 */
	public function looseFloat(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj,
	    $unionMaybeNumeric,
	    $unionNumbers,
	    $unionStrings
	) {
		assertType('bool', $float == $true);
		assertType('bool', $float == $false);
		assertType('bool', $float == $one);
		assertType('bool', $float == 10);
		assertType('bool', $float == $minusOne);
		assertType('bool', $float == $oneStr);
		assertType('bool', $float == $zeroStr);
		assertType('bool', $float == $minusOneStr);
		assertType('bool', $float == $null);
		assertType('false', $float == $emptyArr);
		assertType('false', $float == $phpStr);
		assertType('false', $float == $emptyStr);
		assertType('false', $float == []);
		assertType('false', $float == $arr);
		assertType('bool', $float == $int);
		assertType('true', $float == $float);
		assertType('bool', $float == $bool);
		assertType('bool', $float == $string);
		assertType('false', $float == $obj);
		assertType('false', $float == new \stdClass());
		assertType('bool', $float == $unionMaybeNumeric);
		assertType('bool', $float == $unionNumbers);
		assertType('false', $float == $unionStrings);
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
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param array $arr
	 */
	public function looseBool(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj
	) {
		assertType('bool', $bool == $true);
		assertType('bool', $bool == $false);
		assertType('bool', $bool == $one);
		assertType('bool', $bool == 10);
		assertType('bool', $bool == $minusOne);
		assertType('bool', $bool == $oneStr);
		assertType('bool', $bool == $zeroStr);
		assertType('bool', $bool == $minusOneStr);
		assertType('bool', $bool == $null);
		assertType('bool', $bool == $emptyArr);
		assertType('bool', $bool == $phpStr);
		assertType('bool', $bool == $emptyStr);
		assertType('bool', $bool == $float);
		assertType('bool', $bool == []);
		assertType('bool', $bool == $arr);
		assertType('bool', $bool == $int);
		assertType('bool', $bool == $float);
		assertType('true', $bool == $bool);
		assertType('bool', $bool == $string);
		assertType('bool', $bool == $obj);
		assertType('bool', $bool == new \stdClass());
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
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param array $arr
	 * @param 'a'|'123'|'123.23' $unionMaybeNumeric
	 * @param 1|2|3 $unionNumbers
	 * @param 'a'|'b'|'c' $unionStrings
	 *
	 * https://3v4l.org/RHc0P
	 */
	public function looseInt(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj,
		$unionMaybeNumeric,
		$unionNumbers,
		$unionStrings
	) {
		assertType('bool', $int == $true);
		assertType('bool', $int == $false);
		assertType('bool', $int == $one);
		assertType('bool', $int == 10);
		assertType('bool', $int == $minusOne);
		assertType('bool', $int == $oneStr);
		assertType('bool', $int == $zeroStr);
		assertType('bool', $int == $minusOneStr);
		assertType('bool', $int == $null);
		assertType('false', $int == $emptyArr);
		assertType('false', $int == $phpStr);
		assertType('false', $int == $emptyStr);
		assertType('bool', $int == $float);
		assertType('false', $int == []);
		assertType('false', $int == $arr);
		assertType('true', $int == $int);
		assertType('bool', $int == $float);
		assertType('bool', $int == $bool);
		assertType('bool', $int == $string);
		assertType('bool', $int == $obj);
		assertType('bool', $int == new \stdClass());
		assertType('bool', $int == $unionMaybeNumeric);
		assertType('bool', $int == $unionNumbers);
		assertType('false', $int == $unionStrings);
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
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param array $arr
	 *
	 * https://3v4l.org/RHc0P
	 */
	public function looseString(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj
	) {
		assertType('bool', $string == $true);
		assertType('bool', $string == $false);
		assertType('bool', $string == $one);
		assertType('bool', $string == 10);
		assertType('bool', $string == $minusOne);
		assertType('bool', $string == $oneStr);
		assertType('bool', $string == $zeroStr);
		assertType('bool', $string == $minusOneStr);
		assertType('bool', $string == $null);
		assertType('false', $string == $emptyArr);
		assertType('bool', $string == $phpStr);
		assertType('bool', $string == $emptyStr);
		assertType('bool', $string == $float);
		assertType('false', $string == []);
		assertType('false', $string == $arr);
		assertType('bool', $string == $int);
		assertType('bool', $string == $float);
		assertType('bool', $string == $bool);
		assertType('true', $string == $string);
		assertType('false', $string == $obj);
		assertType('false', $string == new \stdClass());
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
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param array $arr
	 * @param 'a'|'123'|'123.23' $unionMaybeNumeric
	 * @param 1|2|3 $unionNumbers
	 * @param 'a'|'b'|'c' $unionStrings
	 *
	 * https://3v4l.org/RHc0P
	 */
	public function looseNever(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj,
		$unionMaybeNumeric,
		$unionNumbers,
		$unionStrings
	) {
		$never = $this->returnNever();
		assertType('false', $never == $true);
		assertType('false', $never == $false);
		assertType('false', $never == $one);
		assertType('false', $never == $zero);
		assertType('false', $never == 10);
		assertType('false', $never == $minusOne);
		assertType('false', $never == $oneStr);
		assertType('false', $never == $zeroStr);
		assertType('false', $never == $minusOneStr);
		assertType('false', $never == $null);
		assertType('false', $never == $emptyArr);
		assertType('false', $never == $phpStr);
		assertType('false', $never == $emptyStr);
		assertType('false', $never == $float);
		assertType('false', $never == []);
		assertType('false', $never == $arr);
		assertType('true', $never == $never);
		assertType('false', $never == $int);
		assertType('false', $never == $float);
		assertType('false', $never == $bool);
		assertType('false', $never == $string);
		assertType('false', $never == $obj);
		assertType('false', $never == new \stdClass());
		assertType('false', $never == $unionMaybeNumeric);
		assertType('false', $never == $unionNumbers);
		assertType('false', $never == $unionStrings);
	}

	/** @return never */
	function returnNever() {
		exit();
	}

	/**
	 * @param never $never
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param array $arr
	 * @param 'a'|'123'|'123.23' $unionMaybeNumeric
	 * @param 1|2|3 $unionNumbers
	 * @param 'a'|'b'|'c' $unionStrings
	 * @param 'a'|'123'|123|array $unionMaybeArray
	 *
	 * https://3v4l.org/RHc0P
	 */
	public function looseArray(
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj,
		$unionMaybeNumeric,
		$unionNumbers,
		$unionStrings,
		$unionMaybeArray
	) {
		assertType('bool', $arr == $true);
		assertType('bool', $arr == $false);
		assertType('false', $arr == $one);
		assertType('false', $arr == $zero);
		assertType('false', $arr == 10);
		assertType('false', $arr == $minusOne);
		assertType('false', $arr == $oneStr);
		assertType('false', $arr == $zeroStr);
		assertType('false', $arr == $minusOneStr);
		assertType('false', $arr == $null);
		assertType('bool', $arr == $emptyArr);
		assertType('false', $arr == $phpStr);
		assertType('false', $arr == $emptyStr);
		assertType('false', $arr == $float);
		assertType('bool', $arr == []);
		assertType('true', $arr == $arr);
		assertType('false', $arr == $int);
		assertType('false', $arr == $float);
		assertType('bool', $arr == $bool);
		assertType('false', $arr == $string);
		assertType('false', $arr == $obj);
		assertType('false', $arr == new \stdClass());
		assertType('false', $arr == $unionMaybeNumeric);
		assertType('false', $arr == $unionNumbers);
		assertType('false', $arr == $unionStrings);
		assertType('bool', $arr == $unionMaybeArray);
	}

	/**
	 * @param callable $callable
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param array $arr
	 * @param 'a'|'123'|'123.23' $unionMaybeNumeric
	 * @param 1|2|3 $unionNumbers
	 * @param 'a'|'b'|'c' $unionStrings
	 * @param 'a'|'123'|123|array $unionMaybeArray
	 *
	 * https://3v4l.org/RHc0P
	 */
	public function looseCallable(
		$callable,
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj,
		$unionMaybeNumeric,
		$unionNumbers,
		$unionStrings,
		$unionMaybeArray
	) {
		assertType('bool', $callable == 'myFunction');
		assertType('true', $callable == $true);
		assertType('false', $callable == $false);
		assertType('true', $callable == $one);
		assertType('false', $callable == $zero);
		assertType('false', $callable == 10);
		assertType('false', $callable == $minusOne);
		assertType('bool', $callable == $oneStr); // could be false, because invalid function name
		assertType('bool', $callable == $zeroStr); // could be false, because invalid function name
		assertType('bool', $callable == $minusOneStr); // could be false, because invalid function name
		assertType('false', $callable == $null);
		assertType('false', $callable == $emptyArr);
		assertType('bool', $callable == $phpStr);
		assertType('false', $callable == $emptyStr);
		assertType('false', $callable == $float);
		assertType('false', $callable == []);
		assertType('bool', $callable == $arr);
		assertType('false', $callable == $int);
		assertType('false', $callable == $float);
		assertType('bool', $callable == $bool);
		assertType('bool', $callable == $string);
		assertType('false', $callable == $obj);
		assertType('false', $callable == new \stdClass());
		assertType('bool', $callable == $unionMaybeNumeric);
		assertType('false', $callable == $unionNumbers);
		assertType('bool', $callable == $unionStrings);
		assertType('bool', $callable == $unionMaybeArray);
	}

	/**
	 * @param callable $callable
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param array $arr
	 * @param 'a'|'123'|'123.23' $unionMaybeNumeric
	 * @param 1|2|3 $unionNumbers
	 * @param 'a'|'b'|'c' $unionStrings
	 * @param 'a'|'123'|123|array $unionMaybeArray
	 *
	 * https://3v4l.org/RHc0P
	 */
	public function looseUnion(
		$callable,
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj,
		$unionMaybeNumeric,
		$unionNumbers,
		$unionStrings,
		$unionMaybeArray
	) {
		assertType('true', $unionMaybeNumeric == $true);
		assertType('false', $unionMaybeNumeric == $false);
		assertType('false', $unionMaybeNumeric == $one);
		assertType('false', $unionMaybeNumeric == $zero);
		assertType('false', $unionMaybeNumeric == 10);
		assertType('false', $unionMaybeNumeric == $minusOne);
		assertType('false', $unionMaybeNumeric == $oneStr);
		assertType('false', $unionMaybeNumeric == $zeroStr);
		assertType('false', $unionMaybeNumeric == $minusOneStr);
		assertType('false', $unionMaybeNumeric == $null);
		assertType('false', $unionMaybeNumeric == $emptyArr);
		assertType('false', $unionMaybeNumeric == $phpStr);
		assertType('false', $unionMaybeNumeric == $emptyStr);
		assertType('false', $unionMaybeNumeric == []);
		assertType('false', $unionMaybeNumeric == $arr);
		assertType('bool', $unionMaybeNumeric == $int);
		assertType('bool', $unionMaybeNumeric == $float);
		assertType('bool', $unionMaybeNumeric == $bool);
		assertType('bool', $unionMaybeNumeric == $string);
		assertType('false', $unionMaybeNumeric == $obj);
		assertType('false', $unionMaybeNumeric == new \stdClass());
		assertType('false', $unionMaybeNumeric == $unionNumbers);
		assertType('bool', $unionMaybeNumeric == $unionStrings);
		assertType('bool', $unionMaybeNumeric == $unionMaybeArray);

		assertType('true', $unionNumbers == $true);
		assertType('false', $unionNumbers == $false);
		assertType('bool', $unionNumbers == $one);
		assertType('false', $unionNumbers == $zero);
		assertType('false', $unionNumbers == 10);
		assertType('false', $unionNumbers == $minusOne);
		assertType('bool', $unionNumbers == $oneStr);
		assertType('false', $unionNumbers == $zeroStr);
		assertType('false', $unionNumbers == $minusOneStr);
		assertType('false', $unionNumbers == $null);
		assertType('false', $unionNumbers == $emptyArr);
		assertType('false', $unionNumbers == $phpStr);
		assertType('false', $unionNumbers == $emptyStr);
		assertType('false', $unionNumbers == []);
		assertType('false', $unionNumbers == $arr);
		assertType('bool', $unionNumbers == $int);
		assertType('bool', $unionNumbers == $float);
		assertType('bool', $unionNumbers == $bool);
		assertType('bool', $unionNumbers == $string);
		assertType('bool', $unionNumbers == $obj);
		assertType('bool', $unionNumbers == new \stdClass());
		assertType('false', $unionNumbers == $unionMaybeNumeric);
		assertType('false', $unionNumbers == $unionStrings);
		assertType('bool', $unionNumbers == $unionMaybeArray);

		assertType('true', $unionStrings == $true);
		assertType('false', $unionStrings == $false);
		assertType('false', $unionStrings == $one);
		assertType('false', $unionStrings == $zero);
		assertType('false', $unionStrings == 10);
		assertType('false', $unionStrings == $minusOne);
		assertType('false', $unionStrings == $oneStr);
		assertType('false', $unionStrings == $zeroStr);
		assertType('false', $unionStrings == $minusOneStr);
		assertType('false', $unionStrings == $null);
		assertType('false', $unionStrings == $emptyArr);
		assertType('false', $unionStrings == $phpStr);
		assertType('false', $unionStrings == $emptyStr);
		assertType('false', $unionStrings == []);
		assertType('false', $unionStrings == $arr);
		assertType('bool', $unionStrings == $int);
		assertType('bool', $unionStrings == $float);
		assertType('bool', $unionStrings == $bool);
		assertType('bool', $unionStrings == $string);
		assertType('false', $unionStrings == $obj);
		assertType('false', $unionStrings == new \stdClass());
		assertType('bool', $unionStrings == $unionMaybeNumeric);
		assertType('false', $unionStrings == $unionNumbers);
		assertType('bool', $unionStrings == $unionMaybeArray);

		assertType('bool', $unionMaybeArray == $true);
		assertType('bool', $unionMaybeArray == $false);
		assertType('false', $unionMaybeArray == $one);
		assertType('false', $unionMaybeArray == $zero);
		assertType('false', $unionMaybeArray == 10);
		assertType('false', $unionMaybeArray == $minusOne);
		assertType('false', $unionMaybeArray == $oneStr);
		assertType('false', $unionMaybeArray == $zeroStr);
		assertType('false', $unionMaybeArray == $minusOneStr);
		assertType('false', $unionMaybeArray == $null);
		assertType('bool', $unionMaybeArray == $emptyArr);
		assertType('false', $unionMaybeArray == $phpStr);
		assertType('false', $unionMaybeArray == $emptyStr);
		assertType('bool', $unionMaybeArray == $float);
		assertType('bool', $unionMaybeArray == []);
		assertType('bool', $unionMaybeArray == $arr);
		assertType('bool', $unionMaybeArray == $int);
		assertType('bool', $unionMaybeArray == $float);
		assertType('bool', $unionMaybeArray == $bool);
		assertType('bool', $unionMaybeArray == $string);
		assertType('false', $unionMaybeArray == $obj);
		assertType('false', $unionMaybeArray == new \stdClass());
		assertType('bool', $unionMaybeArray == $unionMaybeNumeric);
		assertType('false', $unionMaybeArray == $unionNumbers);
		assertType('bool', $unionMaybeArray == $unionStrings);
	}

	/**
	 * @param non-empty-string $nonEmptyString
	 * @param non-empty-array $nonEmptyArray
	 * @param callable $callable
	 * @param true $true
	 * @param false $false
	 * @param 1 $one
	 * @param 0 $zero
	 * @param -1 $minusOne
	 * @param '1' $oneStr
	 * @param '0' $zeroStr
	 * @param '-1' $minusOneStr
	 * @param null $null
	 * @param array{} $emptyArr
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param array $arr
	 * @param 'a'|'123'|'123.23' $unionMaybeNumeric
	 * @param 1|2|3 $unionNumbers
	 * @param 'a'|'b'|'c' $unionStrings
	 * @param 'a'|'123'|123|array $unionMaybeArray
	 *
	 * https://3v4l.org/RHc0P
	 */
	public function looseIntersection(
		$nonEmptyString,
		$nonEmptyArray,
		$true,
		$false,
		$one,
		$zero,
		$minusOne,
		$oneStr,
		$zeroStr,
		$minusOneStr,
		$null,
		$emptyArr,
		$phpStr,
		$emptyStr,
		array $arr,
		int $int,
		float $float,
		bool $bool,
		string $string,
		object $obj,
		$unionMaybeNumeric,
		$unionNumbers,
		$unionStrings,
		$unionMaybeArray
	) {
		assertType('bool', $nonEmptyString == $true);
		assertType('false', $nonEmptyString == $false);
		assertType('bool', $nonEmptyString == $one);
		assertType('bool', $nonEmptyString == $zero);
		assertType('bool', $nonEmptyString == 10);
		assertType('bool', $nonEmptyString == $minusOne);
		assertType('bool', $nonEmptyString == $oneStr);
		assertType('bool', $nonEmptyString == $zeroStr);
		assertType('bool', $nonEmptyString == $minusOneStr);
		assertType('false', $nonEmptyString == $null);
		assertType('false', $nonEmptyString == $emptyArr);
		assertType('bool', $nonEmptyString == $phpStr);
		assertType('false', $nonEmptyString == $emptyStr);
		assertType('bool', $nonEmptyString == $float);
		assertType('false', $nonEmptyString == []);
		assertType('false', $nonEmptyString == $arr);
		assertType('bool', $nonEmptyString == $int);
		assertType('bool', $nonEmptyString == $float);
		assertType('bool', $nonEmptyString == $bool);
		assertType('bool', $nonEmptyString == $string);
		assertType('false', $nonEmptyString == $obj);
		assertType('false', $nonEmptyString == new \stdClass());
		assertType('bool', $nonEmptyString == $unionMaybeNumeric);
		assertType('bool', $nonEmptyString == $unionNumbers);
		assertType('bool', $nonEmptyString == $unionStrings);
		assertType('bool', $nonEmptyString == $unionMaybeArray);

		assertType('true', $nonEmptyArray == $true);
		assertType('false', $nonEmptyArray == $false);
		assertType('false', $nonEmptyArray == $one);
		assertType('false', $nonEmptyArray == $zero);
		assertType('false', $nonEmptyArray == 10);
		assertType('false', $nonEmptyArray == $minusOne);
		assertType('false', $nonEmptyArray == $oneStr);
		assertType('false', $nonEmptyArray == $zeroStr);
		assertType('false', $nonEmptyArray == $minusOneStr);
		assertType('false', $nonEmptyArray == $null);
		assertType('false', $nonEmptyArray == $emptyArr);
		assertType('false', $nonEmptyArray == $phpStr);
		assertType('false', $nonEmptyArray == $emptyStr);
		assertType('false', $nonEmptyArray == $float);
		assertType('false', $nonEmptyArray == []);
		assertType('bool', $nonEmptyArray == $arr);
		assertType('false', $nonEmptyArray == $int);
		assertType('false', $nonEmptyArray == $float);
		assertType('bool', $nonEmptyArray == $bool);
		assertType('false', $nonEmptyArray == $string);
		assertType('false', $nonEmptyArray == $obj);
		assertType('false', $nonEmptyArray == new \stdClass());
		assertType('false', $nonEmptyArray == $unionMaybeNumeric);
		assertType('false', $nonEmptyArray == $unionNumbers);
		assertType('false', $nonEmptyArray == $unionStrings);
		assertType('bool', $nonEmptyArray == $unionMaybeArray);
	}
}

function myFunction() {}

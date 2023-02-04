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
	 *
	 * https://3v4l.org/SBBII
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
		object $obj
	) {
		assertType('true', $arr == $true);
		assertType('false', $arr == $false);
		assertType('false', $arr == $one);
		assertType('false', $arr == 10);
		assertType('false', $arr == $minusOne);
		assertType('false', $arr == $oneStr);
		assertType('false', $arr == $zeroStr);
		assertType('false', $arr == $minusOneStr);
		assertType('false', $arr == $null);
		assertType('bool', $arr == $emptyArr);
		assertType('false', $arr == $phpStr);
		assertType('false', $arr == $emptyStr);
		assertType('bool', $arr == []);
		assertType('true', $arr == $arr);
		assertType('false', $arr == $int);
		assertType('false', $arr == $float);
		assertType('bool', $arr == $bool);
		assertType('false', $arr == $string);
		assertType('false', $arr == $obj);
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
		object $obj
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
		assertType('true', $bool == $obj);
		assertType('true', $bool == new \stdClass());
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
		object $obj
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
}

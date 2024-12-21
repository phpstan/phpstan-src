<?php // lint >= 8.0

declare(strict_types=1);

namespace LooseSemanticsPhp8;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param 0 $zero
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayZero(
		$zero,
		$phpStr,
		$emptyStr
	): void
	{
		assertType('false', $zero == $phpStr); // PHP8+ only
		assertType('false', $zero == $emptyStr); // PHP8+ only
	}

	/**
	 * @param 0 $zero
	 * @param 'php' $phpStr
	 */
	public function sayPhpStr(
		$zero,
		$phpStr,
	): void
	{
		assertType('false', $phpStr == $zero); // PHP8+ only
	}

	/**
	 * @param 0 $zero
	 * @param '' $emptyStr
	 */
	public function sayEmptyStr(
		$zero,
		$emptyStr
	): void
	{
		assertType('false', $emptyStr == $zero); // PHP8+ only
	}

	/**
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 * @param int<10, 20> $intRange
	 */
	public function sayInt(
		$emptyStr,
		$phpStr,
		int $int,
		int $intRange
	): void
	{
		assertType('false', $int == $emptyStr);
		assertType('false', $int == $phpStr);
		assertType('false', $int == 'a');

		assertType('false', $intRange == $emptyStr);
		assertType('false', $intRange == $phpStr);
		assertType('false', $intRange == 'a');
	}

}

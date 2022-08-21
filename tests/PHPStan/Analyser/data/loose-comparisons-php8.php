<?php

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
}

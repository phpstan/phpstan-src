<?php // lint < 8.0

declare(strict_types=1);

namespace LooseSemanticsPhp7;

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
		assertType('true', $zero == $phpStr);
		assertType('true', $zero == $emptyStr);
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
		assertType('true', $phpStr == $zero);
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
		assertType('true', $emptyStr == $zero);
	}

	/**
	 * @param 'php' $phpStr
	 * @param '' $emptyStr
	 */
	public function sayInt(
		$emptyStr,
		$phpStr,
		int $int
	): void
	{
		assertType('bool', $int == $emptyStr);
		assertType('bool', $int == $phpStr);
		assertType('bool', $int == 'a');
	}
}

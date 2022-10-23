<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<PossiblyDivisionByZeroRule>
 */
class PossiblyDivisionByZeroRuleTest extends RuleTestCase
{

	private bool $checkNullable;

	private bool $checkUnionTypes;

	private bool $checkExplicitelyMixed;

	protected function getRule(): Rule
	{
		return new PossiblyDivisionByZeroRule(
			new RuleLevelHelper(
				$this->createReflectionProvider(),
				$this->checkNullable,
				false,
				$this->checkUnionTypes,
				$this->checkExplicitelyMixed,
				false,
				false,
			),
			$this->checkNullable,
		);
	}

	public function testRule(): void
	{
		$this->checkNullable = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitelyMixed = true;

		$this->analyse([__DIR__ . '/data/division-by-zero.php'], [
			[
				'Division by "int<-3, 3>" might result in a division by zero.',
				23,
			],
			[
				'Division by "int<-3, -2>|int<0, 2>" might result in a division by zero.',
				24,
			],
			[
				'Division by "int" might result in a division by zero.',
				25,
			],
			[
				'Division by "int|null" might result in a division by zero.',
				26,
			],
			[
				'Division by "mixed" might result in a division by zero.',
				28,
			],
			[
				'Division by "int<min, -1>|int<1, max>|null" might result in a division by zero.',
				35,
			],
			[
				'Division by "int<-3, 3>" might result in a division by zero.',
				43,
			],
			[
				'Division by "int<-3, -2>|int<0, 2>" might result in a division by zero.',
				44,
			],
			[
				'Division by "int" might result in a division by zero.',
				45,
			],
			[
				'Division by "int|null" might result in a division by zero.',
				46,
			],
			[
				'Division by "mixed" might result in a division by zero.',
				48,
			],
			[
				'Division by "int<min, -1>|int<1, max>|null" might result in a division by zero.',
				55,
			],
		]);
	}

	public function testRule2(): void
	{
		$this->checkNullable = true;
		$this->checkUnionTypes = true;
		$this->checkExplicitelyMixed = false;

		$this->analyse([__DIR__ . '/data/division-by-zero.php'], [
			[
				'Division by "int<-3, 3>" might result in a division by zero.',
				23,
			],
			[
				'Division by "int<-3, -2>|int<0, 2>" might result in a division by zero.',
				24,
			],
			[
				'Division by "int" might result in a division by zero.',
				25,
			],
			[
				'Division by "int|null" might result in a division by zero.',
				26,
			],
			[
				'Division by "int<min, -1>|int<1, max>|null" might result in a division by zero.',
				35,
			],
			[
				'Division by "int<-3, 3>" might result in a division by zero.',
				43,
			],
			[
				'Division by "int<-3, -2>|int<0, 2>" might result in a division by zero.',
				44,
			],
			[
				'Division by "int" might result in a division by zero.',
				45,
			],
			[
				'Division by "int|null" might result in a division by zero.',
				46,
			],
			[
				'Division by "int<min, -1>|int<1, max>|null" might result in a division by zero.',
				55,
			],
		]);
	}

	public function testRule3(): void
	{
		$this->checkNullable = false;
		$this->checkUnionTypes = true;
		$this->checkExplicitelyMixed = false;

		$this->analyse([__DIR__ . '/data/division-by-zero.php'], [
			[
				'Division by "int<-3, 3>" might result in a division by zero.',
				23,
			],
			[
				'Division by "int<-3, -2>|int<0, 2>" might result in a division by zero.',
				24,
			],
			[
				'Division by "int" might result in a division by zero.',
				25,
			],
			[
				'Division by "int|null" might result in a division by zero.',
				26,
			],
			[
				'Division by "int<-3, 3>" might result in a division by zero.',
				43,
			],
			[
				'Division by "int<-3, -2>|int<0, 2>" might result in a division by zero.',
				44,
			],
			[
				'Division by "int" might result in a division by zero.',
				45,
			],
			[
				'Division by "int|null" might result in a division by zero.',
				46,
			],
		]);
	}

	public function testRule4(): void
	{
		$this->checkNullable = true;
		$this->checkUnionTypes = false;
		$this->checkExplicitelyMixed = false;

		$this->analyse([__DIR__ . '/data/division-by-zero.php'], [
			[
				'Division by "int<-3, 3>" might result in a division by zero.',
				23,
			],
			[
				'Division by "int" might result in a division by zero.',
				25,
			],
			[
				'Division by "int|null" might result in a division by zero.',
				26,
			],
			[
				'Division by "int<-3, 3>" might result in a division by zero.',
				43,
			],
			[
				'Division by "int" might result in a division by zero.',
				45,
			],
			[
				'Division by "int|null" might result in a division by zero.',
				46,
			],
		]);
	}

}

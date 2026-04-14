<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DoWhileLoopConstantConditionRule>
 */
class DoWhileLoopConstantConditionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DoWhileLoopConstantConditionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->getTypeSpecifier(),
					[],
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				$this->shouldTreatPhpDocTypesAsCertain(),
			),
			new PossiblyImpureTipHelper(true),
			$this->shouldTreatPhpDocTypesAsCertain(),
			true,
		);
	}

	public function testBug5865(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5865.php'], []);
	}

	public function testBug6189(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6189.php'], []);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/do-while-loop.php'], [
			[
				'Do-while loop condition is always true.',
				12,
			],
			[
				'Do-while loop condition is always false.',
				37,
			],
			[
				'Do-while loop condition is always false.',
				46,
			],
			[
				'Do-while loop condition is always true.',
				64,
			],
			[
				'Do-while loop condition is always false.',
				73,
			],
			[
				'Do-while loop condition is always false.',
				105,
			],
			[
				'Do-while loop condition is always false.',
				115,
			],
			[
				'Do-while loop condition is always false.',
				138,
			],
			[
				'Do-while loop condition is always false.',
				152,
			],
		]);
	}

}

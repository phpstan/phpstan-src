<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<WhileLoopAlwaysTrueConditionRule>
 */
class WhileLoopAlwaysTrueConditionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new WhileLoopAlwaysTrueConditionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->getTypeSpecifier(),
					[],
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				$this->shouldTreatPhpDocTypesAsCertain(),
			),
			$this->shouldTreatPhpDocTypesAsCertain(),
			true,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/while-loop-true.php'], [
			[
				'While loop condition is always true.',
				10,
			],
			[
				'While loop condition is always true.',
				20,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'While loop condition is always true.',
				65,
			],
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<WhileLoopAlwaysFalseConditionRule>
 */
class WhileLoopAlwaysFalseConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain = true;

	protected function getRule(): Rule
	{
		return new WhileLoopAlwaysFalseConditionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->getTypeSpecifier(),
					[],
					$this->treatPhpDocTypesAsCertain,
				),
				$this->treatPhpDocTypesAsCertain,
			),
			$this->treatPhpDocTypesAsCertain,
			true,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/while-loop-false.php'], [
			[
				'While loop condition is always false.',
				10,
			],
			[
				'While loop condition is always false.',
				20,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

}

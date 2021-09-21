<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

/**
 * @extends \PHPStan\Testing\RuleTestCase<WhileLoopAlwaysFalseConditionRule>
 */
class WhileLoopAlwaysFalseConditionRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var bool */
	private $treatPhpDocTypesAsCertain = true;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new WhileLoopAlwaysFalseConditionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					$this->createReflectionProvider(),
					$this->getTypeSpecifier(),
					[],
					$this->treatPhpDocTypesAsCertain
				),
				$this->treatPhpDocTypesAsCertain
			),
			$this->treatPhpDocTypesAsCertain
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

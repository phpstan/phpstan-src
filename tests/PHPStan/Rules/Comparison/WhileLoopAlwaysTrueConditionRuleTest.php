<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

/**
 * @extends \PHPStan\Testing\RuleTestCase<WhileLoopAlwaysTrueConditionRule>
 */
class WhileLoopAlwaysTrueConditionRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var bool */
	private $treatPhpDocTypesAsCertain = true;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new WhileLoopAlwaysTrueConditionRule(
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

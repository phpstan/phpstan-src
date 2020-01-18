<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ElseIfConstantConditionRule>
 */
class ElseIfConstantConditionRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var bool */
	private $treatPhpDocTypesAsCertain;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new ElseIfConstantConditionRule(
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
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/elseif-condition.php'], [
			[
				'Elseif condition is always true.',
				18,
			],
		]);
	}

	public function testDoNotReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/elseif-condition-not-phpdoc.php'], [
			[
				'Elseif condition is always true.',
				18,
			],
		]);
	}

	public function testReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/elseif-condition-not-phpdoc.php'], [
			[
				'Elseif condition is always true.',
				18,
			],
			[
				'Elseif condition is always true.',
				24,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

}

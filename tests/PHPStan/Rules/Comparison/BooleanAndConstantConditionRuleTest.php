<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

/**
 * @extends \PHPStan\Testing\RuleTestCase<BooleanAndConstantConditionRule>
 */
class BooleanAndConstantConditionRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var bool */
	private $treatPhpDocTypesAsCertain;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new BooleanAndConstantConditionRule(
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
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse([__DIR__ . '/data/boolean-and.php'], [
			[
				'Left side of && is always true.',
				15,
			],
			[
				'Right side of && is always true.',
				19,
			],
			[
				'Left side of && is always false.',
				24,
			],
			[
				'Right side of && is always false.',
				27,
			],
			[
				'Right side of && is always false.',
				30,
			],
			[
				'Right side of && is always true.',
				33,
			],
			[
				'Right side of && is always true.',
				36,
			],
			[
				'Right side of && is always true.',
				39,
			],
			[
				'Result of && is always false.',
				50,
			],
			[
				'Result of && is always true.',
				54,
				$tipText,
			],
			[
				'Result of && is always false.',
				60,
			],
			[
				'Result of && is always true.',
				64,
				//$tipText,
			],
			[
				'Result of && is always false.',
				66,
				//$tipText,
			],
			[
				'Result of && is always false.',
				125,
			],
			[
				'Left side of && is always false.',
				139,
			],
			[
				'Right side of && is always false.',
				141,
			],
			[
				'Left side of && is always true.',
				145,
			],
			[
				'Right side of && is always true.',
				147,
			],
		]);
	}

	public function testDoNotReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/boolean-and-not-phpdoc.php'], [
			[
				'Left side of && is always true.',
				24,
			],
			[
				'Right side of && is always true.',
				30,
			],
		]);
	}

	public function testReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse([__DIR__ . '/data/boolean-and-not-phpdoc.php'], [
			[
				'Result of && is always false.',
				14,
				$tipText,
			],
			[
				'Left side of && is always true.',
				24,
			],
			[
				'Left side of && is always true.',
				27,
				$tipText,
			],
			[
				'Right side of && is always true.',
				30,
			],
			[
				'Right side of && is always true.',
				33,
				$tipText,
			],
		]);
	}

	public function dataTreatPhpDocTypesAsCertainRegression(): array
	{
		return [
			[
				true,
			],
			[
				false,
			],
		];
	}

	/**
	 * @dataProvider dataTreatPhpDocTypesAsCertainRegression
	 * @param bool $treatPhpDocTypesAsCertain
	 */
	public function testTreatPhpDocTypesAsCertainRegression(bool $treatPhpDocTypesAsCertain): void
	{
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->analyse([__DIR__ . '/data/boolean-and-treat-phpdoc-types-regression.php'], []);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<BooleanOrConstantConditionRule>
 */
class BooleanOrConstantConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	private bool $bleedingEdge = false;

	private bool $reportAlwaysTrueInLastCondition = false;

	protected function getRule(): Rule
	{
		return new BooleanOrConstantConditionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					$this->createReflectionProvider(),
					$this->getTypeSpecifier(),
					[],
					$this->treatPhpDocTypesAsCertain,
					true,
				),
				$this->treatPhpDocTypesAsCertain,
				true,
			),
			$this->treatPhpDocTypesAsCertain,
			$this->bleedingEdge,
			$this->reportAlwaysTrueInLastCondition,
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
		$this->analyse([__DIR__ . '/data/boolean-or.php'], [
			[
				'Left side of || is always true.',
				15,
			],
			[
				'Right side of || is always true.',
				19,
			],
			[
				'Left side of || is always false.',
				24,
			],
			[
				'Right side of || is always false.',
				27,
			],
			[
				'Right side of || is always true.',
				30,
			],
			[
				'Result of || is always true.',
				33,
			],
			[
				'Right side of || is always false.',
				36,
			],
			[
				'Right side of || is always false.',
				39,
			],
			[
				'Result of || is always true.',
				50,
				$tipText,
			],
			[
				'Result of || is always true.',
				54,
				$tipText,
			],
			[
				'Result of || is always true.',
				61,
			],
			[
				'Result of || is always true.',
				65,
			],
			[
				'Left side of || is always false.',
				77,
			],
			[
				'Right side of || is always false.',
				79,
			],
			[
				'Left side of || is always true.',
				83,
			],
			[
				'Right side of || is always true.',
				85,
			],
			[
				'Left side of || is always true.',
				101,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Right side of || is always true.',
				110,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testRuleLogicalOr(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse([__DIR__ . '/data/boolean-logical-or.php'], [
			[
				'Left side of || is always true.',
				15,
			],
			[
				'Right side of || is always true.',
				19,
			],
			[
				'Left side of || is always false.',
				24,
			],
			[
				'Right side of || is always false.',
				27,
			],
			[
				'Right side of || is always true.',
				30,
			],
			[
				'Result of || is always true.',
				33,
			],
			[
				'Right side of || is always false.',
				36,
			],
			[
				'Right side of || is always false.',
				39,
			],
			[
				'Result of || is always true.',
				50,
				$tipText,
			],
			[
				'Result of || is always true.',
				54,
				$tipText,
			],
			[
				'Result of || is always true.',
				61,
			],
			[
				'Result of || is always true.',
				65,
			],
			[
				'Left side of || is always false.',
				77,
			],
			[
				'Right side of || is always false.',
				79,
			],
			[
				'Left side of || is always true.',
				83,
			],
			[
				'Right side of || is always true.',
				85,
			],
		]);
	}

	public function testRuleLogicalOrBleedingEdge(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->bleedingEdge = true;
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse([__DIR__ . '/data/boolean-logical-or.php'], [
			[
				'Left side of or is always true.',
				15,
			],
			[
				'Right side of or is always true.',
				19,
			],
			[
				'Left side of or is always false.',
				24,
			],
			[
				'Right side of or is always false.',
				27,
			],
			[
				'Right side of or is always true.',
				30,
			],
			[
				'Result of or is always true.',
				33,
			],
			[
				'Right side of or is always false.',
				36,
			],
			[
				'Right side of or is always false.',
				39,
			],
			[
				'Result of or is always true.',
				50,
				$tipText,
			],
			[
				'Result of or is always true.',
				54,
				$tipText,
			],
			[
				'Result of or is always true.',
				61,
			],
			[
				'Result of or is always true.',
				65,
			],
			[
				'Left side of or is always false.',
				77,
			],
			[
				'Right side of or is always false.',
				79,
			],
			[
				'Left side of or is always true.',
				83,
			],
			[
				'Right side of or is always true.',
				85,
			],
		]);
	}

	public function testDoNotReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/boolean-or-not-phpdoc.php'], [
			[
				'Left side of || is always true.',
				24,
			],
			[
				'Right side of || is always true.',
				30,
			],
		]);
	}

	public function testReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse([__DIR__ . '/data/boolean-or-not-phpdoc.php'], [
			[
				'Result of || is always true.',
				14,
				$tipText,
			],
			[
				'Left side of || is always true.',
				24,
			],
			[
				'Left side of || is always true.',
				27,
				$tipText,
			],
			[
				'Right side of || is always true.',
				30,
			],
			[
				'Right side of || is always true.',
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
	 */
	public function testTreatPhpDocTypesAsCertainRegression(bool $treatPhpDocTypesAsCertain): void
	{
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->analyse([__DIR__ . '/data/boolean-or-treat-phpdoc-types-regression.php'], []);
	}

	public function testBug6258(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6258.php'], []);
	}

	public function testBug2741(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-2741-or.php'], [
			[
				'Right side of || is always false.',
				21,
			],
		]);
	}

	public function testBug7881(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-7881.php'], []);
	}

	public function dataReportAlwaysTrueInLastCondition(): iterable
	{
		yield [false, [
			[
				'Left side of || is always true.',
				23,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Right side of || is always true.',
				50,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Result of || is always true.',
				81,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]];
		yield [true, [
			[
				'Left side of || is always true.',
				13,
			],
			[
				'Left side of || is always true.',
				23,
			],
			[
				'Right side of || is always true.',
				40,
			],
			[
				'Right side of || is always true.',
				50,
			],
			[
				'Result of || is always true.',
				69,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Result of || is always true.',
				81,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]];
	}

	/**
	 * @dataProvider dataReportAlwaysTrueInLastCondition
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testReportAlwaysTrueInLastCondition(bool $reportAlwaysTrueInLastCondition, array $expectedErrors): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
		$this->analyse([__DIR__ . '/data/boolean-or-report-always-true-last-condition.php'], $expectedErrors);
	}

	public function testBug6551(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->reportAlwaysTrueInLastCondition = true;
		$this->analyse([__DIR__ . '/data/bug-6551.php'], []);
	}

}

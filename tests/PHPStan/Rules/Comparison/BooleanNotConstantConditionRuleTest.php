<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @extends RuleTestCase<BooleanNotConstantConditionRule>
 */
class BooleanNotConstantConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	private bool $reportAlwaysTrueInLastCondition = false;

	protected function getRule(): Rule
	{
		return new BooleanNotConstantConditionRule(
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
			$this->reportAlwaysTrueInLastCondition,
			true,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/boolean-not.php'], [
			[
				'Negated boolean expression is always false.',
				13,
			],
			[
				'Negated boolean expression is always true.',
				18,
			],
			[
				'Negated boolean expression is always false.',
				33,
			],
			[
				'Negated boolean expression is always false.',
				40,
			],
			[
				'Negated boolean expression is always true.',
				46,
			],
			[
				'Negated boolean expression is always false.',
				50,
			],
			[
				'Negated boolean expression is always true.',
				67,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testDoNotReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/boolean-not-not-phpdoc.php'], [
			[
				'Negated boolean expression is always false.',
				16,
			],
		]);
	}

	public function testReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/boolean-not-not-phpdoc.php'], [
			[
				'Negated boolean expression is always false.',
				16,
			],
			[
				'Negated boolean expression is always false.',
				20,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public static function dataTreatPhpDocTypesAsCertainRegression(): array
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

	#[DataProvider('dataTreatPhpDocTypesAsCertainRegression')]
	public function testTreatPhpDocTypesAsCertainRegression(bool $treatPhpDocTypesAsCertain): void
	{
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->analyse([__DIR__ . '/../DeadCode/data/bug-without-issue-1.php'], []);
	}

	public function testBug6473(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6473.php'], []);
	}

	public function testBug5317(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-5317.php'], [
			[
				'Negated boolean expression is always false.',
				18,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testBug8797(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8797.php'], []);
	}

	public function testBug13797(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-13797.php'], []);
	}

	public function testBug7937(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-7937.php'], []);
	}

	public static function dataReportAlwaysTrueInLastCondition(): iterable
	{
		yield [false, [
			[
				'Negated boolean expression is always true.',
				23,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Negated boolean expression is always false.',
				40,
			],
			[
				'Negated boolean expression is always false.',
				50,
			],
		]];
		yield [true, [
			[
				'Negated boolean expression is always true.',
				13,
			],
			[
				'Negated boolean expression is always true.',
				23,
			],
			[
				'Negated boolean expression is always false.',
				40,
			],
			[
				'Negated boolean expression is always false.',
				50,
			],
		]];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	#[DataProvider('dataReportAlwaysTrueInLastCondition')]
	public function testReportAlwaysTrueInLastCondition(bool $reportAlwaysTrueInLastCondition, array $expectedErrors): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
		$this->analyse([__DIR__ . '/data/boolean-not-report-always-true-last-condition.php'], $expectedErrors);
	}

}

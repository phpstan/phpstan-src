<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ConstantLooseComparisonRule>
 */
class ConstantLooseComparisonRuleTest extends RuleTestCase
{

	private bool $checkAlwaysTrueStrictComparison;

	private bool $treatPhpDocTypesAsCertain = true;

	private bool $reportAlwaysTrueInLastCondition = false;

	protected function getRule(): Rule
	{
		return new ConstantLooseComparisonRule($this->checkAlwaysTrueStrictComparison, $this->treatPhpDocTypesAsCertain, $this->reportAlwaysTrueInLastCondition);
	}

	public function testRule(): void
	{
		$this->checkAlwaysTrueStrictComparison = false;
		$this->analyse([__DIR__ . '/data/loose-comparison.php'], [
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				20,
			],
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				27,
			],
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				33,
			],
			[
				'Loose comparison using != between 3 and 3 will always evaluate to false.',
				48,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testRuleAlwaysTrue(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/loose-comparison.php'], [
			[
				"Loose comparison using == between 0 and '0' will always evaluate to true.",
				16,
			],
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				20,
			],
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				27,
			],
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				33,
			],
			[
				"Loose comparison using == between 0 and '0' will always evaluate to true.",
				35,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Loose comparison using != between 3 and 3 will always evaluate to false.',
				48,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testBug8485(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-8485.php'], [
			[
				'Loose comparison using == between Bug8485\E::c and Bug8485\E::c will always evaluate to true.',
				21,
			],
			[
				'Loose comparison using == between Bug8485\F::c and Bug8485\E::c will always evaluate to false.',
				26,
			],
			[
				'Loose comparison using == between Bug8485\F::c and Bug8485\E::c will always evaluate to false.',
				31,
			],
			[
				'Loose comparison using == between Bug8485\F and Bug8485\E will always evaluate to false.',
				38,
			],
			[
				'Loose comparison using == between Bug8485\F and Bug8485\E::c will always evaluate to false.',
				43,
			],
		]);
	}

	public function dataReportAlwaysTrueInLastCondition(): iterable
	{
		yield [false, [
			[
				'Loose comparison using == between 1 and 1 will always evaluate to true.',
				21,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]];
		yield [true, [
			[
				'Loose comparison using == between 1 and 1 will always evaluate to true.',
				12,
			],
			[
				'Loose comparison using == between 1 and 1 will always evaluate to true.',
				21,
			],
		]];
	}

	/**
	 * @dataProvider dataReportAlwaysTrueInLastCondition
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testReportAlwaysTrueInLastCondition(bool $reportAlwaysTrueInLastCondition, array $expectedErrors): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
		$this->analyse([__DIR__ . '/data/loose-comparison-report-always-true-last-condition.php'], $expectedErrors);
	}

	public function dataTreatPhpDocTypesAsCertain(): iterable
	{
		yield [false, []];
		yield [true, [
			[
				'Loose comparison using == between 3 and 3 will always evaluate to true.',
				14,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]];
	}

	/**
	 * @dataProvider dataTreatPhpDocTypesAsCertain
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testTreatPhpDocTypesAsCertain(bool $treatPhpDocTypesAsCertain, array $expectedErrors): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->analyse([__DIR__ . '/data/loose-comparison-treat-phpdoc-types.php'], $expectedErrors);
	}

	public function testLooseUnion(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/loose-comparison-union.php'], [
			[
				'Loose comparison using == between 0|1|false and 2 will always evaluate to false.',
				9,
			],
		]);
	}

}

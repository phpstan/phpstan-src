<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<BooleanNotConstantConditionRule>
 */
class BooleanNotConstantConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new BooleanNotConstantConditionRule(
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
		$this->analyse([__DIR__ . '/../DeadCode/data/bug-without-issue-1.php'], []);
	}

	public function testBug6473(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

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

}

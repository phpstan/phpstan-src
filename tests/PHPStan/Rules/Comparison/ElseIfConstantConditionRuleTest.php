<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ElseIfConstantConditionRule>
 */
class ElseIfConstantConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	private bool $reportAlwaysTrueInLastCondition = false;

	protected function getRule(): Rule
	{
		return new ElseIfConstantConditionRule(
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

	public static function dataRule(): iterable
	{
		yield [false, [
			[
				'Elseif condition is always true.',
				56,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Elseif condition is always false.',
				73,
			],
			[
				'Elseif condition is always false.',
				77,
			],
		]];

		yield [true, [
			[
				'Elseif condition is always true.',
				18,
			],
			[
				'Elseif condition is always true.',
				52,
			],
			[
				'Elseif condition is always true.',
				56,
			],
			[
				'Elseif condition is always false.',
				73,
			],
			[
				'Elseif condition is always false.',
				77,
			],
		]];
	}

	/**
	 * @dataProvider dataRule
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testRule(bool $reportAlwaysTrueInLastCondition, array $expectedErrors): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
		$this->analyse([__DIR__ . '/data/elseif-condition.php'], $expectedErrors);
	}

	public function testDoNotReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/elseif-condition-not-phpdoc.php'], [
			[
				'Elseif condition is always true.',
				46,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/elseif-condition-not-phpdoc.php'], [
			[
				'Elseif condition is always true.',
				46,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Elseif condition is always true.',
				56,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testBug11674(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-11674.php'], [
			[
				'Elseif condition is always false.',
				28,
			],
			[
				'Elseif condition is always false.',
				36,
			],
		]);
	}

	public function testBug6947(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6947.php'], [
			[
				'Elseif condition is always false.',
				13,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

}

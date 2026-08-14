<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class BooleanAndConstantConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	private bool $reportAlwaysTrueInLastCondition = false;

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new BooleanAndConstantConditionRule(
				new ConstantConditionRuleHelper(
					$this->treatPhpDocTypesAsCertain,
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->treatPhpDocTypesAsCertain,
				$this->reportAlwaysTrueInLastCondition,
				true,
			),
			new ImpossibleCheckTypeFunctionCallRule(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->treatPhpDocTypesAsCertain,
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->treatPhpDocTypesAsCertain,
				$this->reportAlwaysTrueInLastCondition,
				true,
			),
			new ImpossibleCheckTypeMethodCallRule(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->treatPhpDocTypesAsCertain,
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->treatPhpDocTypesAsCertain,
				$this->reportAlwaysTrueInLastCondition,
				true,
			),
			new ImpossibleCheckTypeStaticMethodCallRule(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->treatPhpDocTypesAsCertain,
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->treatPhpDocTypesAsCertain,
				$this->reportAlwaysTrueInLastCondition,
				true,
			),
			new FunctionCallConstantConditionRule(),
			new ConstantConditionInTraitRule(),
		]);
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
				'Result of && is always false.',
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
			[
				'Left side of && is always true.',
				178,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Right side of && is always true.',
				178,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testRuleLogicalAnd(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse([__DIR__ . '/data/boolean-logical-and.php'], [
			[
				'Left side of and is always true.',
				15,
			],
			[
				'Right side of and is always true.',
				19,
			],
			[
				'Left side of and is always false.',
				24,
			],
			[
				'Right side of and is always false.',
				27,
			],
			[
				'Result of and is always false.',
				30,
			],
			[
				'Right side of and is always true.',
				33,
			],
			[
				'Right side of and is always true.',
				36,
			],
			[
				'Right side of and is always true.',
				39,
			],
			[
				'Result of and is always false.',
				50,
			],
			[
				'Result of and is always true.',
				54,
				$tipText,
			],
			[
				'Result of and is always false.',
				60,
			],
			[
				'Result of and is always true.',
				64,
				//$tipText,
			],
			[
				'Result of and is always false.',
				66,
				//$tipText,
			],
			[
				'Result of and is always false.',
				125,
			],
			[
				'Left side of and is always false.',
				139,
			],
			[
				'Right side of and is always false.',
				141,
			],
			[
				'Left side of and is always true.',
				145,
			],
			[
				'Right side of and is always true.',
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
		$this->analyse([__DIR__ . '/data/boolean-and-treat-phpdoc-types-regression.php'], []);
	}

	public function testBugComposerDependentVariables(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-composer-dependent-variables.php'], []);
	}

	public function testBug2231(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-2231.php'], [
			[
				'Result of && is always false.',
				21,
			],
		]);
	}

	public function testBug1746(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-1746.php'], [
			[
				'Left side of && is always true.',
				20,
			],
		]);
	}

	public function testBug4666(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4666.php'], []);
	}

	public function testBug2870(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-2870.php'], []);
	}

	public function testBug2741(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-2741.php'], [
			[
				'Right side of && is always false.',
				21,
			],
		]);
	}

	public function testBug7270(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-7270.php'], []);
	}

	public function testBug5743(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-5743.php'], []);
	}

	public static function dataBug4969(): iterable
	{
		yield [false, []];
		yield [true, [
			[
				'Call to function is_string() with string will always evaluate to true.',
				12,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Result of && is always false.',
				15,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	#[DataProvider('dataBug4969')]
	public function testBug4969(bool $treatPhpDocTypesAsCertain, array $expectedErrors): void
	{
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->analyse([__DIR__ . '/data/bug-4969.php'], $expectedErrors);
	}

	public static function dataReportAlwaysTrueInLastCondition(): iterable
	{
		yield [false, [
			[
				'Left side of && is always true.',
				23,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Right side of && is always true.',
				50,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Result of && is always true.',
				81,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]];
		yield [true, [
			[
				'Left side of && is always true.',
				13,
			],
			[
				'Left side of && is always true.',
				23,
			],
			[
				'Right side of && is always true.',
				40,
			],
			[
				'Right side of && is always true.',
				50,
			],
			[
				'Result of && is always true.',
				69,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Result of && is always true.',
				81,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
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
		$this->analyse([__DIR__ . '/data/boolean-and-report-always-true-last-condition.php'], $expectedErrors);
	}

	public function testBug5365(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->reportAlwaysTrueInLastCondition = true;
		$this->analyse([__DIR__ . '/data/bug-5365.php'], []);
	}

	public function testBug11908(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->reportAlwaysTrueInLastCondition = true;
		$this->analyse([__DIR__ . '/data/bug-11908.php'], []);
	}

	public function testBug8555(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8555.php'], []);
	}

	public function testSelfContradiction(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/self-contradiction.php'], [
			[
				'Result of && is always false.',
				25,
			],
			[
				'Result of && is always false.',
				51,
			],
			[
				'Result of && is always false.',
				77,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Result of && is always false.',
				103,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14807(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-14807.php'], []);
	}

	public function testBug14878(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-14878.php'], []);
	}

	public function testInTrait(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->reportAlwaysTrueInLastCondition = true;
		$this->analyse([__DIR__ . '/data/boolean-and-in-trait.php'], [
			[
				'Left side of && is always true.',
				19,
			],
		]);
	}

}

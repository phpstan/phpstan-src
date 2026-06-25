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
class ElseIfConstantConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	private bool $reportAlwaysTrueInLastCondition = false;

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new ElseIfConstantConditionRule(
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
					$this->getTypeSpecifier(),
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
					$this->getTypeSpecifier(),
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
					$this->getTypeSpecifier(),
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
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	#[DataProvider('dataRule')]
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

	#[RequiresPhp('>= 8.0.0')]
	public function testBug11674(): void
	{
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

	#[RequiresPhp('>= 8.0.0')]
	public function testBug6947(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6947.php'], [
			[
				'Elseif condition is always false.',
				13,
				"• Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.\n• If Bug6947\HelloWorld::getValue() is impure, add <fg=cyan>@phpstan-impure</> PHPDoc tag above its declaration. Learn more: <fg=cyan>https://phpstan.org/blog/remembering-and-forgetting-returned-values</>",
			],
		]);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testInTrait(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/elseif-condition-in-trait.php'], [
			[
				'Elseif condition is always false.',
				23,
			],
		]);
	}

}

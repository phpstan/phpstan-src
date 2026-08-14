<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class DoWhileLoopConstantConditionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new DoWhileLoopConstantConditionRule(
				new ConstantConditionRuleHelper(
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->shouldTreatPhpDocTypesAsCertain(),
				true,
			),
			new ImpossibleCheckTypeFunctionCallRule(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->shouldTreatPhpDocTypesAsCertain(),
				true,
				true,
			),
			new ImpossibleCheckTypeMethodCallRule(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->shouldTreatPhpDocTypesAsCertain(),
				true,
				true,
			),
			new ImpossibleCheckTypeStaticMethodCallRule(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->shouldTreatPhpDocTypesAsCertain(),
				true,
				true,
			),
			new FunctionCallConstantConditionRule(),
			new ConstantConditionInTraitRule(),
		]);
	}

	public function testBug6189(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6189.php'], []);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/do-while-loop.php'], [
			[
				'Do-while loop condition is always true.',
				12,
			],
			[
				'Do-while loop condition is always false.',
				37,
			],
			[
				'Do-while loop condition is always false.',
				46,
			],
			[
				'Do-while loop condition is always true.',
				64,
			],
			[
				'Do-while loop condition is always false.',
				73,
			],
			[
				'Do-while loop condition is always false.',
				105,
			],
			[
				'Do-while loop condition is always false.',
				115,
			],
			[
				'Do-while loop condition is always false.',
				138,
			],
			[
				'Do-while loop condition is always false.',
				152,
			],
		]);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testInTrait(): void
	{
		$this->analyse([__DIR__ . '/data/do-while-in-trait.php'], [
			[
				'Do-while loop condition is always false.',
				19,
			],
		]);
	}

}

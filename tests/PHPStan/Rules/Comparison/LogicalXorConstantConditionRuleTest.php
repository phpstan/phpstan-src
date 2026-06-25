<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class LogicalXorConstantConditionRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new LogicalXorConstantConditionRule(
				new ConstantConditionRuleHelper(
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->shouldTreatPhpDocTypesAsCertain(),
				false,
				true,
			),
			new ImpossibleCheckTypeFunctionCallRule(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->getTypeSpecifier(),
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->shouldTreatPhpDocTypesAsCertain(),
				false,
				true,
			),
			new ImpossibleCheckTypeMethodCallRule(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->getTypeSpecifier(),
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->shouldTreatPhpDocTypesAsCertain(),
				false,
				true,
			),
			new ImpossibleCheckTypeStaticMethodCallRule(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->getTypeSpecifier(),
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				$this->shouldTreatPhpDocTypesAsCertain(),
				false,
				true,
			),
			new FunctionCallConstantConditionRule(),
			new ConstantConditionInTraitRule(),
		]);
	}

	public function testRule(): void
	{
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse([__DIR__ . '/data/logical-xor.php'], [
			[
				'Left side of xor is always true.',
				14,
			],
			[
				'Right side of xor is always false.',
				14,
			],
			[
				'Left side of xor is always false.',
				17,
			],
			[
				'Right side of xor is always true.',
				17,
			],
			[
				'Left side of xor is always true.',
				20,
				$tipText,
			],
			[
				'Right side of xor is always true.',
				20,
				$tipText,
			],
			[
				'Left side of xor is always true.',
				24,
			],
			[
				'Right side of xor is always false.',
				24,
			],
		]);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testInTrait(): void
	{
		$this->analyse([__DIR__ . '/data/logical-xor-in-trait.php'], [
			[
				'Left side of xor is always false.',
				19,
			],
		]);
	}

}

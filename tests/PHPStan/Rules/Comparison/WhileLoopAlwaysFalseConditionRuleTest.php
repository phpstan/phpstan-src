<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class WhileLoopAlwaysFalseConditionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new WhileLoopAlwaysFalseConditionRule(
				new ConstantConditionRuleHelper(
					new ImpossibleCheckTypeHelper(
						self::createReflectionProvider(),
						$this->getTypeSpecifier(),
						$this->shouldTreatPhpDocTypesAsCertain(),
					),
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				$this->shouldTreatPhpDocTypesAsCertain(),
				true,
			),
			new ConstantConditionInTraitRule(),
		]);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/while-loop-false.php'], [
			[
				'While loop condition is always false.',
				10,
			],
			[
				'While loop condition is always false.',
				20,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testInTrait(): void
	{
		$this->analyse([__DIR__ . '/data/while-false-in-trait.php'], [
			[
				'While loop condition is always false.',
				19,
			],
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class WhileLoopAlwaysTrueConditionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new WhileLoopAlwaysTrueConditionRule(
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
		$this->analyse([__DIR__ . '/data/while-loop-true.php'], [
			[
				'While loop condition is always true.',
				10,
			],
			[
				'While loop condition is always true.',
				20,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'While loop condition is always true.',
				65,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRulePHP81(): void
	{
		$this->analyse([__DIR__ . '/data/while-loop-true-php81.php'], []);
	}

	public function testBug10054(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10054.php'], []);
	}

	public function testBug6189(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6189.php'], [
			[
				'While loop condition is always true.',
				33,
			],
			[
				'While loop condition is always true.',
				44,
			],
		]);
	}

	public function testInTrait(): void
	{
		$this->analyse([__DIR__ . '/data/while-true-in-trait.php'], [
			[
				'While loop condition is always true.',
				19,
			],
		]);
	}

}

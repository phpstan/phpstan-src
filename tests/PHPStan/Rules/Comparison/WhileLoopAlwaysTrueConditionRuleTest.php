<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<WhileLoopAlwaysTrueConditionRule>
 */
class WhileLoopAlwaysTrueConditionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new WhileLoopAlwaysTrueConditionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					$this->getTypeSpecifier(),
					[],
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				$this->shouldTreatPhpDocTypesAsCertain(),
			),
			new PossiblyImpureTipHelper(true),
			$this->shouldTreatPhpDocTypesAsCertain(),
			true,
		);
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

	#[RequiresPhp('>= 8.1')]
	public function testRulePHP81(): void
	{
		$this->analyse([__DIR__ . '/data/while-loop-true-php81.php'], []);
	}

	public function testBug10054(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10054.php'], []);
	}

	public function testBug5865(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5865-while.php'], []);
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

}

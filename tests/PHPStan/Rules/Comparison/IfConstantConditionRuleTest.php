<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class IfConstantConditionRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new IfConstantConditionRule(
				new ConstantConditionRuleHelper(
					new ImpossibleCheckTypeHelper(
						self::createReflectionProvider(),
						$this->getTypeSpecifier(),
						[],
						$this->treatPhpDocTypesAsCertain,
					),
					$this->treatPhpDocTypesAsCertain,
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				$this->treatPhpDocTypesAsCertain,
				true,
			),
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
		require_once __DIR__ . '/data/function-definition.php';
		$this->analyse([__DIR__ . '/data/if-condition.php'], [
			[
				'If condition is always true.',
				40,
			],
			[
				'If condition is always false.',
				45,
			],
			[
				'If condition is always true.',
				96,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'If condition is always true.',
				110,
			],
			[
				'If condition is always true.',
				113,
			],
			[
				'If condition is always true.',
				127,
			],
			[
				'If condition is always true.',
				287,
			],
			[
				'If condition is always false.',
				291,
			],
		]);
	}

	public function testDoNotReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/if-condition-not-phpdoc.php'], [
			[
				'If condition is always true.',
				16,
			],
		]);
	}

	public function testReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/if-condition-not-phpdoc.php'], [
			[
				'If condition is always true.',
				16,
			],
			[
				'If condition is always true.',
				20,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testBug4043(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4043.php'], [
			[
				'If condition is always false.',
				43,
			],
			[
				'If condition is always true.',
				50,
			],
		]);
	}

	public function testBug5370(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-5370.php'], []);
	}

	public function testBug6902(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6902.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug8485(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		// reported by ConstantLooseComparisonRule instead
		$this->analyse([__DIR__ . '/data/bug-8485.php'], []);
	}

	public function testBug4302(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4302.php'], []);
	}

	public function testBug7491(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-7491.php'], []);
	}

	public function testBug2499(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-2499.php'], []);
	}

	public function testBug10561(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-10561.php'], []);
	}

	public function testBug4912(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4912.php'], []);
	}

	public function testBug4864(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4864.php'], []);
	}

	public function testBug8926(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8926.php'], []);
	}

	public function testBug11417(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-11417.php'], [
			[
				'If condition is always true.',
				66,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testBug10903(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-10903.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug13384b(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/../TooWideTypehints/data/bug-13384b.php'], [
			[
				'If condition is always false.',
				23,
			],
		]);
	}

	public function testBug7699(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-7699.php'], []);
	}

	public function testBug4284(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4284.php'], [
			[
				'If condition is always true.',
				25,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testInTrait(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/if-condition-in-trait.php'], [
			[
				'If condition is always true.',
				19,
			],
		]);
	}

	public function testBug6822(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-6822.php'], []);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class UnnecessaryTernaryOperatorRuleTest extends RuleTestCase
{

	private bool $unnecessaryTernary = true;

	private bool $treatPhpDocTypesAsCertain = true;

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new UnnecessaryTernaryOperatorRule(
				self::getContainer()->getByType(ConstantConditionRuleHelper::class),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::createReflectionProvider(),
				self::getContainer()->getByType(ExprPrinter::class),
				$this->unnecessaryTernary,
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

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unnecessary-ternary.php'], [
			[
				'Ternary operator is unnecessary because it always results in $i.',
				21,
			],
			[
				'Ternary operator is unnecessary because it always results in $i.',
				31,
			],
			[
				'Ternary operator is unnecessary because it always results in $i.',
				36,
			],
			[
				'Ternary operator is unnecessary because it always results in $foo.',
				41,
			],
			[
				'Ternary operator is unnecessary because it always results in $i.',
				46,
			],
			[
				'Ternary operator is unnecessary because it always results in $foo.',
				54,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Short ternary operator ?: is unnecessary because it always results in $foo.',
				59,
			],
			[
				'Short ternary operator ?: is unnecessary because it always results in $b.',
				64,
			],
			[
				'Short ternary operator ?: is unnecessary because it always results in $a.',
				73,
			],
			[
				'Ternary operator is unnecessary because it always results in $s.',
				78,
			],
			[
				'Short ternary operator ?: is unnecessary because it always results in $foo.',
				151,
			],
			[
				'Ternary operator is unnecessary because it always results in $s.',
				190,
			],
			[
				'Ternary operator is unnecessary because it always results in $foo.',
				195,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRuleWithoutTreatingPhpDocTypesAsCertain(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/unnecessary-ternary.php'], [
			[
				'Ternary operator is unnecessary because it always results in $i.',
				21,
			],
			[
				'Ternary operator is unnecessary because it always results in $i.',
				31,
			],
			[
				'Ternary operator is unnecessary because it always results in $i.',
				36,
			],
			[
				'Ternary operator is unnecessary because it always results in $foo.',
				41,
			],
			[
				'Ternary operator is unnecessary because it always results in $i.',
				46,
			],
			[
				'Short ternary operator ?: is unnecessary because it always results in $foo.',
				59,
			],
			[
				'Short ternary operator ?: is unnecessary because it always results in $b.',
				64,
			],
			[
				'Short ternary operator ?: is unnecessary because it always results in $a.',
				73,
			],
			[
				'Ternary operator is unnecessary because it always results in $s.',
				78,
			],
			[
				'Short ternary operator ?: is unnecessary because it always results in $foo.',
				151,
			],
			[
				'Ternary operator is unnecessary because it always results in $s.',
				190,
			],
			[
				'Ternary operator is unnecessary because it always results in $foo.',
				195,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRuleDisabled(): void
	{
		$this->unnecessaryTernary = false;
		$this->analyse([__DIR__ . '/data/unnecessary-ternary.php'], []);
	}

}

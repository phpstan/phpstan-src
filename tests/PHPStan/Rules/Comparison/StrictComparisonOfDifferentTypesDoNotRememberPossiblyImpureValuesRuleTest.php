<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class StrictComparisonOfDifferentTypesDoNotRememberPossiblyImpureValuesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new StrictComparisonOfDifferentTypesRule(
				self::getContainer()->getByType(RicherScopeGetTypeHelper::class),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				true,
				false,
				true,
			),
			new ConstantConditionInTraitRule(),
		]);
	}

	public function testPropertyAfterPossiblyImpureCall(): void
	{
		$this->analyse([__DIR__ . '/data/strict-comparison-property-after-possibly-impure-call.php'], [
			[
				"Strict comparison using === between 'ready' and 'complete' will always evaluate to false.",
				41,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/doNotRememberPossiblyImpureValues.neon',
			],
		);
	}

}

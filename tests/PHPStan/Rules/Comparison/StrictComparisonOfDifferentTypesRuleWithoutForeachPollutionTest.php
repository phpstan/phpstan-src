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
class StrictComparisonOfDifferentTypesRuleWithoutForeachPollutionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new StrictComparisonOfDifferentTypesRule(
				self::getContainer()->getByType(RicherScopeGetTypeHelper::class),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				true,
				false,
				true,
			),
			new ConstantConditionInTraitRule(),
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/../without-foreach-pollution.neon',
			],
		);
	}

	public function testBug14446(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-14446.php'], []);
	}

}

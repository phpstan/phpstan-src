<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class NumberComparisonOperatorsConstantConditionRuleWithoutForeachPollutionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new NumberComparisonOperatorsConstantConditionRule(
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				true,
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

	public function testBug13984(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13984.php'], []);
	}

}

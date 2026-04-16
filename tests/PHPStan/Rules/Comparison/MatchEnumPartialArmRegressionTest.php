<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * Regression test: when a match arm has multiple enum case conditions and the
 * enum fast-path analysis cannot handle all of them (e.g. because the condition
 * type is narrowed to a single case), the analysis must not partially consume
 * enum cases from the unused pool. Partial consumption caused the remaining
 * type to become NeverType, corrupting the scope for subsequent match expressions.
 *
 * @extends RuleTestCase<MatchCallbackScopeRegressionRule>
 */
class MatchEnumPartialArmRegressionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MatchCallbackScopeRegressionRule();
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testEnumPartialArmConsumption(): void
	{
		$this->analyse([__DIR__ . '/data/match-enum-partial-arm-regression.php'], [
			[
				'MatchEnumPartialArmRegression\MyEnum::A',
				19,
			],
			[
				'MatchEnumPartialArmRegression\MyEnum::A',
				25,
			],
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * Regression test: the scope passed to the MatchExpressionNode callback
 * must reflect the original match condition type, not the merged arm body
 * scope which contains narrowed types from individual arms.
 *
 * @extends RuleTestCase<MatchCallbackScopeRegressionRule>
 */
class MatchCallbackScopeRegressionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MatchCallbackScopeRegressionRule();
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testExhaustiveMatchCallbackScope(): void
	{
		$this->analyse([__DIR__ . '/data/match-callback-scope-regression.php'], [
			[
				'MatchCallbackScopeRegression\Suit',
				23,
			],
		]);
	}

}

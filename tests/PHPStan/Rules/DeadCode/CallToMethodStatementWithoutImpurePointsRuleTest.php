<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToMethodStatementWithoutImpurePointsRule>
 */
class CallToMethodStatementWithoutImpurePointsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToMethodStatementWithoutImpurePointsRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-method-without-impure-points.php'], [
			[
				'Call to method CallToMethodWithoutImpurePoints\x::myFunc() on a separate line has no effect.',
				7,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\x::myFunc() on a separate line has no effect.',
				8,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				21,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				27,
			],
		]);
	}

	protected function getCollectors(): array
	{
		return [
			new PossiblyPureMethodCallCollector(),
			new MethodWithoutImpurePointsCollector(),
		];
	}

}

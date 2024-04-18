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
				'Call to method CallToMethodWithoutImpurePoints\finalX::myFunc() on a separate line has no effect.',
				7,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\finalX::myFunc() on a separate line has no effect.',
				8,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\foo::finalFunc() on a separate line has no effect.',
				30,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\finalSubSubY::mySubSubFunc() on a separate line has no effect.',
				39,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\y::myFinalBaseFunc() on a separate line has no effect.',
				40,
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

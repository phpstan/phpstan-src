<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\DeadCode\CallToMethodStatementWithoutImpurePointsRule;
use PHPStan\Rules\DeadCode\MethodWithoutImpurePointsCollector;
use PHPStan\Rules\DeadCode\PossiblyPureMethodCallCollector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToStaticMethodStatementWithoutImpurePointsRule>
 */
class CallToStaticMethodStatementWithoutImpurePointsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToStaticMethodStatementWithoutImpurePointsRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-static-method-without-impure-points.php'], [
			[
				'Call to method CallToStaticMethodWithoutImpurePoints\X::myFunc() on a separate line has no effect.',
				6,
			],
			[
				'Call to method CallToStaticMethodWithoutImpurePoints\X::myFunc() on a separate line has no effect.',
				7,
			],
			[
				'Call to method CallToStaticMethodWithoutImpurePoints\X::myFunc() on a separate line has no effect.',
				14,
			],
			[
				'Call to method CallToStaticMethodWithoutImpurePoints\X::myFunc() on a separate line has no effect.',
				16,
			],
			[
				'Call to method CallToStaticMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				18,
			],
			[
				'Call to method CallToStaticMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				20,
			],
			[
				'Call to method CallToStaticMethodWithoutImpurePoints\SubSubY::mySubSubFunc() on a separate line has no effect.',
				21,
			],
			[
				'Call to method CallToStaticMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				48,
			],
			[
				'Call to method CallToStaticMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				53,
			],
			[
				'Call to method CallToStaticMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				58,
			],
		]);
	}

	protected function getCollectors(): array
	{
		return [
			new PossiblyPureStaticCallCollector(),
			new MethodWithoutImpurePointsCollector(),
		];
	}

}

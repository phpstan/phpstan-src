<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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
				'Call to method CallToMethodWithoutImpurePoints\y::myFinalBaseFunc() on a separate line has no effect.',
				36,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				39,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\finalSubSubY::mySubSubFunc() on a separate line has no effect.',
				40,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\y::myFinalBaseFunc() on a separate line has no effect.',
				41,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\AbstractFoo::myFunc() on a separate line has no effect.',
				119,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\CallsPrivateMethodWithoutImpurePoints::doBar() on a separate line has no effect.',
				127,
			],
		]);
	}

	public function testBug11011(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->analyse([__DIR__ . '/data/bug-11011.php'], [
			[
				'Call to method Bug11011\AnotherPureImpl::doFoo() on a separate line has no effect.',
				32,
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

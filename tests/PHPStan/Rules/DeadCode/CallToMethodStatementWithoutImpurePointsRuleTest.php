<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CallToMethodStatementWithoutImpurePointsRule>
 */
class CallToMethodStatementWithoutImpurePointsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToMethodStatementWithoutImpurePointsRule(new PossiblyPureCallTransitivePurityResolver(self::createReflectionProvider()));
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
				'Call to method CallToMethodWithoutImpurePoints\finalX::myFunc() on a separate line has no effect.',
				21,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\finalX::myFunc() on a separate line has no effect.',
				27,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\foo::finalFunc() on a separate line has no effect.',
				30,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				35,
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
				'Call to method CallToMethodWithoutImpurePoints\y::myFinalBaseFunc() on a separate line has no effect.',
				61,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\AbstractFoo::myFunc() on a separate line has no effect.',
				139,
			],
			[
				'Call to method CallToMethodWithoutImpurePoints\CallsPrivateMethodWithoutImpurePoints::doBar() on a separate line has no effect.',
				147,
			],
		]);
	}

	public function testTransitive(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-method-without-impure-points-transitive.php'], [
			[
				'Call to method CallToMethodWithoutImpurePointsTransitive\Foo::pureBase() on a separate line has no effect.',
				37,
			],
			[
				'Call to method CallToMethodWithoutImpurePointsTransitive\Foo::transitive() on a separate line has no effect.',
				38,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug11011(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11011.php'], [
			[
				'Call to method Bug11011\AnotherPureImpl::doFoo() on a separate line has no effect.',
				32,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug12379(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12379.php'], []);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testPipeOperator(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-method-without-impure-points-pipe.php'], [
			[
				'Call to method CallToMethodWithoutImpurePointsPipe\Foo::maybePure() on a separate line has no effect.',
				17,
			],
			[
				'Call to method CallToMethodWithoutImpurePointsPipe\Foo::maybePure() on a separate line has no effect.',
				18,
			],
		]);
	}

	protected function getCollectors(): array
	{
		$purityResolver = new PossiblyPureCallTransitivePurityResolver(self::createReflectionProvider());

		return [
			new PossiblyPureMethodCallCollector(),
			new MethodWithoutImpurePointsCollector($purityResolver),
			new FunctionWithoutImpurePointsCollector($purityResolver),
			new ConstructorWithoutImpurePointsCollector($purityResolver),
		];
	}

}

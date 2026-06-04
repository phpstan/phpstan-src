<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CallToStaticMethodStatementWithoutImpurePointsRule>
 */
class CallToStaticMethodStatementWithoutImpurePointsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToStaticMethodStatementWithoutImpurePointsRule(new PossiblyPureCallTransitivePurityResolver(self::createReflectionProvider()));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-static-method-without-impure-points.php'], [
			[
				'Call to CallToStaticMethodWithoutImpurePoints\X::myFunc() on a separate line has no effect.',
				6,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePoints\X::myFunc() on a separate line has no effect.',
				7,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePoints\X::myFunc() on a separate line has no effect.',
				16,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				18,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				20,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePoints\SubSubY::mySubSubFunc() on a separate line has no effect.',
				21,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePoints\SubSubY::mySubSubCallSelfFunc() on a separate line has no effect.',
				22,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePoints\SubSubY::mySubSubCallParentFunc() on a separate line has no effect.',
				23,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePoints\SubSubY::mySubSubCallStaticFunc() on a separate line has no effect.',
				24,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				48,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				53,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePoints\y::myFunc() on a separate line has no effect.',
				58,
			],
		]);
	}

	public function testThrows(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-static-method-without-impure-points-throws.php'], [
			[
				'Call to CallToStaticMethodWithoutImpurePointsThrows\Foo::noThrowsStatic() on a separate line has no effect.',
				34,
			],
		]);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testPipeOperator(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-static-method-without-impure-points-pipe.php'], [
			[
				'Call to CallToStaticMethodWithoutImpurePointsPipe\Foo::doFoo() on a separate line has no effect.',
				16,
			],
			[
				'Call to CallToStaticMethodWithoutImpurePointsPipe\Foo::doFoo() on a separate line has no effect.',
				17,
			],
		]);
	}

	protected function getCollectors(): array
	{
		$purityResolver = new PossiblyPureCallTransitivePurityResolver(self::createReflectionProvider());

		return [
			new PossiblyPureStaticCallCollector(),
			new MethodWithoutImpurePointsCollector($purityResolver),
			new FunctionWithoutImpurePointsCollector($purityResolver),
			new ConstructorWithoutImpurePointsCollector($purityResolver),
		];
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CallToFunctionStatementWithoutImpurePointsRule>
 */
class CallToFunctionStatementWithoutImpurePointsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToFunctionStatementWithoutImpurePointsRule(new PossiblyPureCallTransitivePurityResolver(self::createReflectionProvider()));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-function-without-impure-points.php'], [
			[
				'Call to function CallToFunctionWithoutImpurePoints\myFunc() on a separate line has no effect.',
				29,
			],
		]);
	}

	public function testTransitive(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-function-without-impure-points-transitive.php'], [
			[
				'Call to function CallToFunctionWithoutImpurePointsTransitive\pureBase() on a separate line has no effect.',
				32,
			],
			[
				'Call to function CallToFunctionWithoutImpurePointsTransitive\pureTransitive() on a separate line has no effect.',
				33,
			],
			[
				'Call to function CallToFunctionWithoutImpurePointsTransitive\pureTransitive2() on a separate line has no effect.',
				34,
			],
		]);
	}

	public function testThrows(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-function-without-impure-points-throws.php'], [
			[
				'Call to function CallToFunctionWithoutImpurePointsThrows\noThrowsFunc() on a separate line has no effect.',
				29,
			],
		]);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testPipeOperator(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-function-without-impure-points-pipe.php'], [
			[
				'Call to function CallToFunctionWithoutImpurePointsPipe\myFunc() on a separate line has no effect.',
				9,
			],
			[
				'Call to function CallToFunctionWithoutImpurePointsPipe\myFunc() on a separate line has no effect.',
				10,
			],
		]);
	}

	protected function getCollectors(): array
	{
		$purityResolver = new PossiblyPureCallTransitivePurityResolver(self::createReflectionProvider());

		return [
			new PossiblyPureFuncCallCollector(self::createReflectionProvider()),
			new FunctionWithoutImpurePointsCollector($purityResolver),
			new MethodWithoutImpurePointsCollector($purityResolver),
			new ConstructorWithoutImpurePointsCollector($purityResolver),
		];
	}

}

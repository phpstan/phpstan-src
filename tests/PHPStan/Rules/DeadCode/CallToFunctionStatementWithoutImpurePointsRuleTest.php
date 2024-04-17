<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToFunctionStatementWithoutImpurePointsRule>
 */
class CallToFunctionStatementWithoutImpurePointsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToFunctionStatementWithoutImpurePointsRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-function-without-impure-points.php'], [
			[
				'Call to function CallToFunctionWithoutImpurePoints\myFunc() on a separate line has no effect.',
				19,
			],
		]);
	}

	protected function getCollectors(): array
	{
		return [
			new PossiblyPureFuncCallCollector($this->createReflectionProvider()),
			new FunctionWithoutImpurePointsCollector(),
		];
	}

}

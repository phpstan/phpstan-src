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
		return new CallToFunctionStatementWithoutImpurePointsRule();
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
		return [
			new PossiblyPureFuncCallCollector(self::createReflectionProvider()),
			new FunctionWithoutImpurePointsCollector(),
		];
	}

}

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

	public function testBug14757(): void
	{
		require_once __DIR__ . '/data/bug-14757-function-definition.php';
		$this->analyse([__DIR__ . '/data/bug-14757-function-call.php'], [
			[
				'Call to function Bug14757Func\emptyFunc() on a separate line has no effect.',
				6,
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
			new PossiblyPureFuncCallCollector(
				self::createReflectionProvider(),
				self::getContainer()->getByType(EmptyBodyCallableDetector::class),
			),
			new FunctionWithoutImpurePointsCollector(),
		];
	}

}

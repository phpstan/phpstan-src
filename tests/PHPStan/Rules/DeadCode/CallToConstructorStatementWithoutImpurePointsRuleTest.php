<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToConstructorStatementWithoutImpurePointsRule>
 */
class CallToConstructorStatementWithoutImpurePointsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToConstructorStatementWithoutImpurePointsRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-constructor-without-impure-points.php'], [
			[
				'Call to new CallToConstructorWithoutImpurePoints\Foo() on a separate line has no effect.',
				15,
			],
		]);
	}

	protected function getCollectors(): array
	{
		return [
			new PossiblyPureNewCollector($this->createReflectionProvider()),
			new ConstructorWithoutImpurePointsCollector(),
		];
	}

}

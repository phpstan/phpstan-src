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
		return new CallToConstructorStatementWithoutImpurePointsRule(new PossiblyPureCallTransitivePurityResolver(self::createReflectionProvider()));
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

	public function testTransitive(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-constructor-without-impure-points-transitive.php'], [
			[
				'Call to new CallToConstructorWithoutImpurePointsTransitive\PureCtor() on a separate line has no effect.',
				42,
			],
		]);
	}

	public function testThrows(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-constructor-without-impure-points-throws.php'], [
			[
				'Call to new CallToConstructorWithoutImpurePointsThrows\NoThrows() on a separate line has no effect.',
				39,
			],
		]);
	}

	protected function getCollectors(): array
	{
		$purityResolver = new PossiblyPureCallTransitivePurityResolver(self::createReflectionProvider());

		return [
			new PossiblyPureNewCollector(self::createReflectionProvider()),
			new ConstructorWithoutImpurePointsCollector($purityResolver),
			new MethodWithoutImpurePointsCollector($purityResolver),
			new FunctionWithoutImpurePointsCollector($purityResolver),
		];
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToConstructorStatementWithoutSideEffectsRule>
 */
class CallToConstructorStatementWithoutSideEffectsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToConstructorStatementWithoutSideEffectsRule($this->createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/constructor-statement-no-side-effects.php'], [
			[
				'Call to Exception::__construct() on a separate line has no effect.',
				6,
			],
			[
				'Call to ConstructorStatementNoSideEffects\ConstructorWithPure::__construct() on a separate line has no effect.',
				57,
			],
			[
				'Call to ConstructorStatementNoSideEffects\ConstructorWithPureAndThrowsVoid::__construct() on a separate line has no effect.',
				58,
			],
		]);
	}

	public function testBug4455(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4455-constructor.php'], []);
	}

}

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
		return new CallToConstructorStatementWithoutSideEffectsRule($this->createReflectionProvider(), true);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/constructor-statement-no-side-effects.php'], [
			[
				'Call to Exception::__construct() on a separate line has no effect.',
				6,
			],
			[
				'Call to new PDOStatement() on a separate line has no effect.',
				11,
			],
			[
				'Call to new stdClass() on a separate line has no effect.',
				12,
			],
			[
				'Call to ConstructorStatementNoSideEffects\ConstructorWithPure::__construct() on a separate line has no effect.',
				57,
			],
			[
				'Call to ConstructorStatementNoSideEffects\ConstructorWithPureAndThrowsVoid::__construct() on a separate line has no effect.',
				58,
			],
			[
				'Call to new ConstructorStatementNoSideEffects\NoConstructor() on a separate line has no effect.',
				68,
			],
		]);
	}

	public function testBug4455(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4455-constructor.php'], []);
	}

}

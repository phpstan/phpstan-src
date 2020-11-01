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
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<FinalPrivateMethodRule> */
class FinalPrivateMethodRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FinalPrivateMethodRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/final-private-method.php'], [
			[
				'Private method FinalPrivateMethod\Foo::foo() cannot be final as it is never overridden by other classes.',
				8,
			],
			[
				'Private method FinalPrivateMethod\FooBarPhp8orHigher::foo() cannot be final as it is never overridden by other classes.',
				39,
			],
			[
				'Private method FinalPrivateMethod\FooBarPhp74OrHigher::foo() cannot be final as it is never overridden by other classes.',
				59,
			],
			[
				'Private method FinalPrivateMethod\FooBarPhp8orHigher::foo() cannot be final as it is never overridden by other classes.',
				69,
			],
		]);
	}

}

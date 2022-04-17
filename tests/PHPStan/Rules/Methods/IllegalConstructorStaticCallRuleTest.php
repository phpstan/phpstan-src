<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IllegalConstructorStaticCallRule>
 */
class IllegalConstructorStaticCallRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IllegalConstructorStaticCallRule();
	}

	public function testMethods(): void
	{
		$this->analyse([__DIR__ . '/data/illegal-constructor-call-rule-test.php'], [
			[
				'Static call to __construct() is only allowed on a parent class in the constructor.',
				31,
			],
			[
				'Static call to __construct() is only allowed on a parent class in the constructor.',
				49,
			],
			[
				'Static call to __construct() is only allowed on a parent class in the constructor.',
				50,
			],
		]);
	}

}

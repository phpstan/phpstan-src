<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IllegalConstructorMethodCallRule>
 */
class IllegalConstructorMethodCallRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IllegalConstructorMethodCallRule();
	}

	public function testMethods(): void
	{
		$this->analyse([__DIR__ . '/data/illegal-constructor-call-rule-test.php'], [
			[
				'Call to __construct() on an existing object is not allowed.',
				13,
			],
			[
				'Call to __construct() on an existing object is not allowed.',
				18,
			],
			[
				'Call to __construct() on an existing object is not allowed.',
				60,
			],
		]);
	}

}

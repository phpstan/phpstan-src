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
				'__construct() should not be called outside constructor.',
				29,
			],
			[
				'__construct() should not be called outside constructor.',
				46,
			],
		]);
	}

}

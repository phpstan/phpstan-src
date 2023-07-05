<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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
				43,
			],
			[
				'Static call to __construct() is only allowed on a parent class in the constructor.',
				44,
			],
			[
				'Static call to __construct() is only allowed on a parent class in the constructor.',
				49,
			],
			[
				'Static call to __construct() is only allowed on a parent class in the constructor.',
				50,
			],
			[
				'Static call to __construct() is only allowed on a parent class in the constructor.',
				100,
			],
		]);
	}

	public function testBug9577(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-9577.php'], []);
	}

}

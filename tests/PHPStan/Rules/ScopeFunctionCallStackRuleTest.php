<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ScopeFunctionCallStackRule>
 */
class ScopeFunctionCallStackRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ScopeFunctionCallStackRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/scope-function-call-stack.php'], [
			[
				"var_dump\nprint_r\nsleep",
				7,
			],
			[
				"var_dump\nprint_r\nsleep",
				10,
			],
			[
				"var_dump\nprint_r\nsleep",
				13,
			],
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ScopeFunctionCallStackWithParametersRule>
 */
class ScopeFunctionCallStackWithParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ScopeFunctionCallStackWithParametersRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/scope-function-call-stack.php'], [
			[
				"var_dump (\$value)\nprint_r (\$value)\nsleep (\$seconds)",
				7,
			],
			[
				"var_dump (\$value)\nprint_r (\$value)\nsleep (\$seconds)",
				10,
			],
			[
				"var_dump (\$value)\nprint_r (\$value)\nsleep (\$seconds)",
				13,
			],
		]);
	}

}

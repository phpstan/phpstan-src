<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<FunctionNeverRule>
 */
class FunctionNeverRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FunctionNeverRule(new NeverRuleHelper());
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			self::markTestSkipped('Test requires PHP 8.1 or greater.');
		}

		$this->analyse([__DIR__ . '/data/function-never.php'], [
			[
				'Function FunctionNever\doBar() always throws an exception, it should have return type "never".',
				18,
			],
			[
				'Function FunctionNever\callsNever() always terminates script execution, it should have return type "never".',
				23,
			],
			[
				'Function FunctionNever\doBaz() always terminates script execution, it should have return type "never".',
				28,
			],
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UselessFunctionReturnValueRule>
 */
class UselessFunctionReturnValueRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UselessFunctionReturnValueRule(
			$this->createReflectionProvider(),
		);
	}

	public function testUselessReturnValue(): void
	{
		$this->analyse([__DIR__ . '/data/useless-fn-return.php'], [
			[
				'Return value of call to function print_r is useless.',
				47,
			],
			[
				'Return value of call to function var_export is useless.',
				56,
			],
			[
				'Return value of call to function print_r is useless.',
				64,
			],
		]);
	}

	public function testUselessReturnValuePhp8(): void
	{
		if (PHP_VERSION_ID < 80000) {
			return;
		}

		$this->analyse([__DIR__ . '/data/useless-fn-return-php8.php'], [
			[
				'Return value of call to function print_r is useless.',
				18,
			],
		]);
	}

}

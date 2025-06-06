<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<FunctionCallableRule>
 */
class FunctionCallableRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		return new FunctionCallableRule(
			$reflectionProvider,
			new RuleLevelHelper($reflectionProvider, true, false, true, false, false, false, true),
			new PhpVersion(PHP_VERSION_ID),
			true,
			true,
		);
	}

	public function testNotSupportedOnOlderVersions(): void
	{
		if (PHP_VERSION_ID >= 80100) {
			self::markTestSkipped('Test runs on PHP < 8.1.');
		}
		$this->analyse([__DIR__ . '/data/function-callable-not-supported.php'], [
			[
				'First-class callables are supported only on PHP 8.1 and later.',
				10,
			],
		]);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			self::markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/function-callable.php'], [
			[
				'Function nonexistent not found.',
				13,
			],
			[
				'Creating callable from string but it might not be a callable.',
				19,
			],
			[
				'Creating callable from 1 but it\'s not a callable.',
				33,
			],
			[
				'Call to function strlen() with incorrect case: StrLen',
				38,
			],
			[
				'Creating callable from 1|(callable(): mixed) but it might not be a callable.',
				47,
			],
			[
				'Creating callable from an unknown class FunctionCallable\Nonexistent.',
				52,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

}

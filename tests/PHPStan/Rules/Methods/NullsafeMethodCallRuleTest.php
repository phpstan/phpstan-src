<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<NullsafeMethodCallRule>
 */
class NullsafeMethodCallRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NullsafeMethodCallRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/nullsafe-method-call-rule.php'], [
			[
				'Using nullsafe method call on non-nullable type Exception. Use -> instead.',
				16,
			],
		]);
	}

}

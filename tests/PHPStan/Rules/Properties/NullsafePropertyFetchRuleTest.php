<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<NullsafePropertyFetchRule>
 */
class NullsafePropertyFetchRuleTest extends RuleTestCase
{

	private bool $strictUnnecessaryNullsafePropertyFetch;

	protected function getRule(): Rule
	{
		return new NullsafePropertyFetchRule($this->strictUnnecessaryNullsafePropertyFetch);
	}

	public function testRule(): void
	{
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/nullsafe-property-fetch-rule.php'], [
			[
				'Using nullsafe property access on non-nullable type Exception. Use -> instead.',
				16,
			],
		]);
	}

	public function testBug6020(): void
	{
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-6020.php'], []);
	}

	public function testBug7109(): void
	{
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-7109.php'], []);
	}

	public function testBug7109Strict(): void
	{
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-7109.php'], [
			[
				'Using nullsafe property access on left side of ?? / in isset / in empty is unnecessary. Use -> instead.',
				15,
			],
			[
				'Using nullsafe property access on left side of ?? / in isset / in empty is unnecessary. Use -> instead.',
				16,
			],
			[
				'Using nullsafe property access on left side of ?? / in isset / in empty is unnecessary. Use -> instead.',
				17,
			],
		]);
	}

}

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

	protected function getRule(): Rule
	{
		return new NullsafePropertyFetchRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/nullsafe-property-fetch-rule.php'], [
			[
				'Using nullsafe property access on non-nullable type Exception. Use -> instead.',
				16,
			],
		]);
	}

	public function testBug6020(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6020.php'], []);
	}

	public function testBug7109(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-7109.php'], []);
	}

	public function testBug5172(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/../../Analyser/data/bug-5172.php'], []);
	}

	public function testBug7980(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/../../Analyser/data/bug-7980.php'], []);
	}

	public function testBug8517(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-8517.php'], []);
	}

	public function testBug9105(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-9105.php'], []);
	}

}

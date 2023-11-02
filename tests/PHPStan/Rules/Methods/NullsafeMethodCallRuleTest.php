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
		$this->analyse([__DIR__ . '/data/nullsafe-method-call-rule.php'], [
			[
				'Using nullsafe method call on non-nullable type Exception. Use -> instead.',
				16,
			],
		]);
	}

	public function testNullsafeVsScalar(): void
	{
		 $this->analyse([__DIR__ . '/../../Analyser/data/nullsafe-vs-scalar.php'], []);
	}

	public function testBug8664(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-8664.php'], []);
	}

	public function testBug9293(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/../../Analyser/data/bug-9293.php'], []);
	}

	public function testBug6922b(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-6922b.php'], []);
	}

}

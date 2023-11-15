<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidCastRule>
 */
class InvalidCastRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new InvalidCastRule($broker, new RuleLevelHelper($broker, true, false, true, false, false, true, false));
	}

	public function testRule(): void
	{
		$this->analyseFileWithErrorsAsComments(__DIR__ . '/data/invalid-cast.php');
	}

	public function testBug5162(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5162.php'], []);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/invalid-cast-nullsafe.php'], [
			[
				'Cannot cast stdClass|null to string.',
				13,
			],
		]);
	}

	public function testCastObjectToString(): void
	{
		$this->analyse([__DIR__ . '/data/cast-object-to-string.php'], [
			[
				'Cannot cast object to string.',
				12,
			],
			[
				'Cannot cast object|string to string.',
				13,
			],
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<InvalidCastRule>
 */
class InvalidCastRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createReflectionProvider();
		return new InvalidCastRule($broker, new RuleLevelHelper($broker, true, false, true, false));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-cast.php'], [
			[
				'Cannot cast stdClass to string.',
				7,
			],
			[
				'Cannot cast stdClass to int.',
				23,
			],
			[
				'Cannot cast stdClass to float.',
				24,
			],
			[
				'Cannot cast Test\\Foo to string.',
				41,
			],
			[
				'Cannot cast array|float|int to string.',
				48,
			],
		]);
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

}

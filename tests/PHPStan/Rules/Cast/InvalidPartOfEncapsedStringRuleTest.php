<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<InvalidPartOfEncapsedStringRule>
 */
class InvalidPartOfEncapsedStringRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new InvalidPartOfEncapsedStringRule(
			new \PhpParser\PrettyPrinter\Standard(),
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false)
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-encapsed-part.php'], [
			[
				'Part $std (stdClass) of encapsed string cannot be cast to string.',
				8,
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/invalid-encapsed-part-nullsafe.php'], [
			[
				'Part $bar?->obj (stdClass|null) of encapsed string cannot be cast to string.',
				11,
			],
		]);
	}

}

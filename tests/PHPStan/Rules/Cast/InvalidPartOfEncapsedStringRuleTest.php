<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidPartOfEncapsedStringRule>
 */
class InvalidPartOfEncapsedStringRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidPartOfEncapsedStringRule(
			new ExprPrinter(new Printer()),
			new RuleLevelHelper(self::createReflectionProvider(), true, false, true, false, false, false, true),
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

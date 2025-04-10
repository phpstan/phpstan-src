<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PrintRule>
 */
class PrintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PrintRule(
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, false, true),
		);
	}

	public function testPrintRule(): void
	{
		$this->analyse([__DIR__ . '/data/print.php'], [
			[
				'Parameter array{} of print cannot be converted to string.',
				5,
			],
			[
				'Parameter stdClass of print cannot be converted to string.',
				7,
			],
			[
				'Parameter Closure(): void of print cannot be converted to string.',
				9,
			],
			[
				'Parameter array{} of print cannot be converted to string.',
				13,
			],
			[
				'Parameter stdClass of print cannot be converted to string.',
				15,
			],
			[
				'Parameter Closure(): void of print cannot be converted to string.',
				17,
			],
			[
				'Parameter \'string\'|array{\'string\'} of print cannot be converted to string.',
				21,
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/print-nullsafe.php'], [
			[
				'Parameter array<int>|null of print cannot be converted to string.',
				15,
			],
		]);
	}

}

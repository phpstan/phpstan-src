<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UnpackIterableInArrayRule>
 */
class UnpackIterableInArrayRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnpackIterableInArrayRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unpack-iterable.php'], [
			[
				'Only iterables can be unpacked, array<int>|null given.',
				21,
			],
			[
				'Only iterables can be unpacked, int given.',
				22,
			],
			[
				'Only iterables can be unpacked, string given.',
				23,
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/unpack-iterable-nullsafe.php'], [
			[
				'Only iterables can be unpacked, array<int>|null given.',
				17,
			],
		]);
	}

}

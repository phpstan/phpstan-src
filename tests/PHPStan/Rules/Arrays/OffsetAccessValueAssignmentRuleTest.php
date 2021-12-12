<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<OffsetAccessValueAssignmentRule>
 */
class OffsetAccessValueAssignmentRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new OffsetAccessValueAssignmentRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/offset-access-value-assignment.php'], [
			[
				'ArrayAccess<int, int> does not accept string.',
				13,
			],
			[
				'ArrayAccess<int, int> does not accept string.',
				15,
			],
			[
				'ArrayAccess<int, int> does not accept string.',
				20,
			],
			[
				'ArrayAccess<int, int> does not accept array<int, string>.',
				21,
			],
			[
				'ArrayAccess<int, int> does not accept string.',
				24,
			],
			[
				'ArrayAccess<int, int> does not accept float.',
				38,
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/offset-access-value-assignment-nullsafe.php'], [
			[
				'ArrayAccess<int, int> does not accept int|null.',
				18,
			],
		]);
	}

	public function testRuleOnArrayAccessIntersection(): void
	{
		$this->analyse([__DIR__ . '/data/offset-access-value-assignment-on-array-access-intersection.php'], [
			[
				'ArrayAccess<int, string> does not accept int.',
				15,
			],
			[
				'ArrayAccess<int, string>&Countable&iterable<int, string> does not accept int.',
				16,
			],
		]);
	}
}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<OffsetAccessValueAssignmentRule>
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

		// TODO: Does not work as expected
		$this->analyse([__DIR__ . '/data/offset-access-value-assignment-nullsafe.php'], [
			[
				'ArrayAccess<int, int> does not accept int|null.',
				18,
			],
		]);
	}

}

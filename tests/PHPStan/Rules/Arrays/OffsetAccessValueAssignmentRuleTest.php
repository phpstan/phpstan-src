<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<OffsetAccessValueAssignmentRule>
 */
class OffsetAccessValueAssignmentRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new OffsetAccessValueAssignmentRule(new RuleLevelHelper(self::createReflectionProvider(), true, false, true, false, false, false, true));
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
			[
				'ArrayAccess<int, string> does not accept int.',
				58,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testRuleWithNullsafeVariant(): void
	{
		$this->analyse([__DIR__ . '/data/offset-access-value-assignment-nullsafe.php'], [
			[
				'ArrayAccess<int, int> does not accept int|null.',
				18,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug5655b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5655b.php'], []);
	}

}

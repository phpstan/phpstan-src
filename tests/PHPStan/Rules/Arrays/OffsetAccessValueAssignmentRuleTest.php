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
		return new OffsetAccessValueAssignmentRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/offset-access-value-assignment.php'], [
			[
				'ArrayAccess<int, int> does not accept string.',
				14,
			],
			[
				'ArrayAccess<int, int> does not accept string.',
				22,
			],
			[
				'ArrayAccess<int, int> does not accept string.',
				31,
			],
			[
				'ArrayAccess<int, int> does not accept array<int, string>.',
				35,
			],
			[
				'ArrayAccess<int, int> does not accept float.',
				54,
			],
		]);
	}

}

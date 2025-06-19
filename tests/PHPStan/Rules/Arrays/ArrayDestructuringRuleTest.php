<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ArrayDestructuringRule>
 */
class ArrayDestructuringRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper(self::createReflectionProvider(), true, false, true, false, false, false, true);

		return new ArrayDestructuringRule(
			$ruleLevelHelper,
			new NonexistentOffsetInArrayDimFetchCheck($ruleLevelHelper, true, false, false),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/array-destructuring.php'], [
			[
				'Cannot use array destructuring on array|null.',
				11,
			],
			[
				'Offset 0 does not exist on array{}.',
				12,
			],
			[
				'Cannot use array destructuring on stdClass.',
				13,
			],
			[
				'Offset 2 does not exist on array{1, 2}.',
				15,
			],
			[
				'Offset \'a\' does not exist on array{b: 1}.',
				22,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testRuleWithNullsafeVariant(): void
	{
		$this->analyse([__DIR__ . '/data/array-destructuring-nullsafe.php'], [
			[
				'Cannot use array destructuring on array|null.',
				10,
			],
		]);
	}

}

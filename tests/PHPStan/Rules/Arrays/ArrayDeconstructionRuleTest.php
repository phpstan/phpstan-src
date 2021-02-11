<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ArrayDeconstructionRule>
 */
class ArrayDeconstructionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false);

		return new ArrayDeconstructionRule(
			$ruleLevelHelper,
			new NonexistentOffsetInArrayDimFetchCheck($ruleLevelHelper, true)
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
				'Offset 0 does not exist on array().',
				12,
			],
			[
				'Cannot use array destructuring on stdClass.',
				13,
			],
			[
				'Offset 2 does not exist on array(1, 2).',
				15,
			],
		]);
	}

}

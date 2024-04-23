<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ArrayDestructuringRule>
 */
class ArrayDestructuringRuleTest extends RuleTestCase
{

	private bool $bleedingEdge = false;

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false);

		return new ArrayDestructuringRule(
			$ruleLevelHelper,
			new NonexistentOffsetInArrayDimFetchCheck($ruleLevelHelper, true, $this->bleedingEdge, false),
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

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/array-destructuring-nullsafe.php'], [
			[
				'Cannot use array destructuring on array|null.',
				10,
			],
		]);
	}

}

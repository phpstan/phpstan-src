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
		$ruleLevelHelper = new RuleLevelHelper(
			self::createReflectionProvider(),
			checkNullables: true,
			checkThisOnly: false,
			checkUnionTypes: true,
			checkExplicitMixed: false,
			checkImplicitMixed: false,
			checkBenevolentUnionTypes: false,
			discoveringSymbolsTip: true,
		);

		return new ArrayDestructuringRule(
			$ruleLevelHelper,
			new NonexistentOffsetInArrayDimFetchCheck(
				$ruleLevelHelper,
				reportMaybes: true,
				reportPossiblyNonexistentGeneralArrayOffset: false,
				reportPossiblyNonexistentConstantArrayOffset: false,
			),
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

	public function testBug14270(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14270.php'], []);
	}

	public function testBug8075(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8075.php'], [
			[
				'Offset \'b\' does not exist on array{a: 0}.',
				12,
			],
			[
				'Offset \'b\' does not exist on array{a: 0}.',
				14,
			],
			[
				'Offset \'b\' does not exist on array{a: 0}.',
				17,
			],
			[
				'Offset \'b\' does not exist on array{a: 0}.',
				24,
			],
			[
				'Offset \'missing\' does not exist on array{name: string, age: int}.',
				36,
			],
			[
				'Offset 2 does not exist on array{string, int}.',
				48,
			],
			[
				'Offset \'z\' does not exist on array{x: int, y: int}.',
				60,
			],
		]);
	}

	public function testBug15013(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15013.php'], [
			[
				'Offset 1 does not exist on array{\'App/Service::foo\'}.',
				8,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
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

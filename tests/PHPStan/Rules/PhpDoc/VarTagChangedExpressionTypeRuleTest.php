<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<VarTagChangedExpressionTypeRule>
 */
class VarTagChangedExpressionTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new VarTagChangedExpressionTypeRule(new VarTagTypeRuleHelper(true));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/var-tag-changed-expr-type.php'], [
			[
				'PHPDoc tag @var with type string is not subtype of native type int.',
				17,
			],
			[
				'PHPDoc tag @var with type string is not subtype of type int.',
				37,
			],
			[
				'PHPDoc tag @var with type string is not subtype of native type int.',
				54,
			],
			[
				'PHPDoc tag @var with type string is not subtype of native type int.',
				73,
			],
		]);
	}

	public function testAssignOfDifferentVariable(): void
	{
		$this->analyse([__DIR__ . '/data/wrong-var-native-type.php'], [
			[
				'PHPDoc tag @var with type string is not subtype of type int.',
				95,
			],
		]);
	}

	public function testBug10130(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10130.php'], [
			[
				'PHPDoc tag @var with type array is not subtype of type array<int>.',
				14,
			],
			[
				'PHPDoc tag @var with type array is not subtype of type list<int>.',
				17,
			],
			[
				'PHPDoc tag @var with type array is not subtype of type array{id: int}.',
				20,
			],
			[
				'PHPDoc tag @var with type array is not subtype of type list<array{id: int}>.',
				23,
			],
		]);
	}

	public function testNarrowListToArray(): void
	{
		$this->analyse([__DIR__ . '/data/narrow-list-to-array.php'], [
			[
				'PHPDoc tag @var with type array<int> is not subtype of type list<int>.',
				13,
			],
		]);
	}

}

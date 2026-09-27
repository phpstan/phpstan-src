<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<VarTagReflectsUsagesRule>
 */
class VarTagReflectsUsagesRuleTest extends RuleTestCase
{

	private bool $checkUnionTypes = true;

	private bool $checkMixed = false;

	protected function getRule(): Rule
	{
		return new VarTagReflectsUsagesRule(new RuleLevelHelper(
			self::createReflectionProvider(),
			checkNullables: true,
			checkThisOnly: false,
			checkUnionTypes: $this->checkUnionTypes,
			checkExplicitMixed: $this->checkMixed,
			checkImplicitMixed: $this->checkMixed,
			checkBenevolentUnionTypes: false,
			discoveringSymbolsTip: true,
		));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/var-tag-reflects-usages.php'], [
			[
				'PHPDoc tag @var with type list<int> does not accept type non-empty-list<\'x\'|int> assigned to $a.',
				56,
			],
			[
				'PHPDoc tag @var with type VarTagReflectsUsages\\Collection<int|string> does not accept type VarTagReflectsUsages\\Collection<int> assigned to $c.',
				83,
			],
			[
				'PHPDoc tag @var with type list<int> does not accept type non-empty-list<\'x\'|int> assigned to $a.',
				99,
			],
			[
				'PHPDoc tag @var with type list<int> does not accept type non-empty-list<\'x\'|int> assigned to $a.',
				109,
			],
			[
				'PHPDoc tag @var with type VarTagReflectsUsages\\Foo|null does not accept type VarTagReflectsUsages\\Bar assigned to $a.',
				156,
			],
			[
				'PHPDoc tag @var with type array<string, int> does not accept type array<string, int|string> assigned to $cache.',
				179,
			],
			[
				'PHPDoc tag @var with type int|null does not accept type string assigned to $instance.',
				200,
			],
			[
				'PHPDoc tag @var with type array<int> does not accept type array<int|string> assigned to $a.',
				251,
			],
			[
				'PHPDoc tag @var with type array<1|2> does not accept type non-empty-array<1|2|3> assigned to $a.',
				269,
			],
			[
				'PHPDoc tag @var with type array<int> does not accept type array<int|string> assigned to $a.',
				288,
			],
			[
				'PHPDoc tag @var with type array<int> does not accept type array<float|int> assigned to $a.',
				290,
			],
			[
				'PHPDoc tag @var with type array<int> does not accept type array<int|string> assigned to $a.',
				293,
			],
			[
				'PHPDoc tag @var with type \'a\'|\'b\' does not accept type \'ax\'|\'bx\' assigned to $s.',
				302,
			],
			[
				'PHPDoc tag @var with type \'a\'|\'b\' does not accept type \'ay\'|\'by\' assigned to $s.',
				303,
			],
			[
				'PHPDoc tag @var with type int<0, 5> does not accept type int<1, 6> assigned to $n.',
				311,
			],
			[
				'PHPDoc tag @var with type list<int> does not accept type non-empty-array<int<0, max>, int> assigned to $l.',
				320,
			],
			[
				'PHPDoc tag @var with type int does not accept type string assigned to $d.',
				329,
			],
		]);
	}

	public function testRuleWithoutUnionTypes(): void
	{
		$this->checkUnionTypes = false;
		$this->analyse([__DIR__ . '/data/var-tag-reflects-usages.php'], [
			[
				'PHPDoc tag @var with type VarTagReflectsUsages\\Collection<int|string> does not accept type VarTagReflectsUsages\\Collection<int> assigned to $c.',
				83,
			],
			[
				'PHPDoc tag @var with type VarTagReflectsUsages\\Foo|null does not accept type VarTagReflectsUsages\\Bar assigned to $a.',
				156,
			],
			[
				'PHPDoc tag @var with type int|null does not accept type string assigned to $instance.',
				200,
			],
			[
				'PHPDoc tag @var with type array<int> does not accept type array<float|int> assigned to $a.',
				290,
			],
			[
				'PHPDoc tag @var with type \'a\'|\'b\' does not accept type \'ax\'|\'bx\' assigned to $s.',
				302,
			],
			[
				'PHPDoc tag @var with type \'a\'|\'b\' does not accept type \'ay\'|\'by\' assigned to $s.',
				303,
			],
			[
				'PHPDoc tag @var with type int does not accept type string assigned to $d.',
				329,
			],
		]);
	}

	public function testRuleWithMixed(): void
	{
		$this->checkMixed = true;
		$this->analyse([__DIR__ . '/data/var-tag-reflects-usages.php'], [
			[
				'PHPDoc tag @var with type list<int> does not accept type non-empty-list<\'x\'|int> assigned to $a.',
				56,
			],
			[
				'PHPDoc tag @var with type VarTagReflectsUsages\\Collection<int|string> does not accept type VarTagReflectsUsages\\Collection<int> assigned to $c.',
				83,
			],
			[
				'PHPDoc tag @var with type list<int> does not accept type non-empty-list<\'x\'|int> assigned to $a.',
				99,
			],
			[
				'PHPDoc tag @var with type list<int> does not accept type non-empty-list<\'x\'|int> assigned to $a.',
				109,
			],
			[
				'PHPDoc tag @var with type VarTagReflectsUsages\\Foo|null does not accept type VarTagReflectsUsages\\Bar assigned to $a.',
				156,
			],
			[
				'PHPDoc tag @var with type array<string, VarTagReflectsUsages\\Foo> does not accept type array<mixed> assigned to $map.',
				167,
			],
			[
				'PHPDoc tag @var with type array<string, int> does not accept type array<string, int|string> assigned to $cache.',
				179,
			],
			[
				'PHPDoc tag @var with type int|null does not accept type string assigned to $instance.',
				200,
			],
			[
				'PHPDoc tag @var with type VarTagReflectsUsages\\Entity<int> does not accept type VarTagReflectsUsages\\Entity<mixed> assigned to $entity.',
				242,
			],
			[
				'PHPDoc tag @var with type array<int> does not accept type array<int|string> assigned to $a.',
				251,
			],
			[
				'PHPDoc tag @var with type array<1|2> does not accept type non-empty-array<1|2|3> assigned to $a.',
				269,
			],
			[
				'PHPDoc tag @var with type array<int> does not accept type array<int|string> assigned to $a.',
				288,
			],
			[
				'PHPDoc tag @var with type array<int> does not accept type array<float|int> assigned to $a.',
				290,
			],
			[
				'PHPDoc tag @var with type array<int> does not accept type array<int|string> assigned to $a.',
				293,
			],
			[
				'PHPDoc tag @var with type \'a\'|\'b\' does not accept type \'ax\'|\'bx\' assigned to $s.',
				302,
			],
			[
				'PHPDoc tag @var with type \'a\'|\'b\' does not accept type \'ay\'|\'by\' assigned to $s.',
				303,
			],
			[
				'PHPDoc tag @var with type int<0, 5> does not accept type int<1, 6> assigned to $n.',
				311,
			],
			[
				'PHPDoc tag @var with type list<int> does not accept type non-empty-array<int<0, max>, int> assigned to $l.',
				320,
			],
			[
				'PHPDoc tag @var with type int does not accept type string assigned to $d.',
				329,
			],
		]);
	}

	public function testBug14198(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14198.php'], [
			[
				'PHPDoc tag @var with type string does not accept type array assigned to $name.',
				9,
			],
		]);
	}

}

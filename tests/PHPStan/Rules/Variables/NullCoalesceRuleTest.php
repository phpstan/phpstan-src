<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\Comparison\ConstantConditionInTraitRule;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class NullCoalesceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new NullCoalesceRule(
				new IssetCheck(
					new PropertyDescriptor(),
					true,
					$this->shouldTreatPhpDocTypesAsCertain(),
				),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				true,
			),
			new ConstantConditionInTraitRule(),
		]);
	}

	public function testCoalesceRule(): void
	{
		$errors = [
			[
				'Property CoalesceRule\FooCoalesce::$string (string) on left side of ?? is not nullable.',
				32,
			],
			[
				'Variable $scalar on left side of ?? always exists and is not nullable.',
				41,
			],
			[
				'Offset \'string\' on array{1, 2, 3} on left side of ?? does not exist.',
				45,
			],
			[
				'Offset \'string\' on array{array{1}, array{2}, array{3}} on left side of ?? does not exist.',
				49,
			],
			[
				'Variable $doesNotExist on left side of ?? is never defined.',
				51,
			],
			[
				'Offset \'dim\' on array{dim: 1, dim-null: 1|null, dim-null-offset: array{a: true|null}, dim-empty: array{}} on left side of ?? always exists and is not nullable.',
				67,
			],
			[
				'Offset \'dim-null-not-set\' on array{dim: 1, dim-null: 1|null, dim-null-offset: array{a: true|null}, dim-empty: array{}} on left side of ?? does not exist.',
				73,
			],
			[
				'Offset \'b\' on array{} on left side of ?? does not exist.',
				79,
			],
			[
				'Expression on left side of ?? is not nullable.',
				81,
			],
			[
				'Property CoalesceRule\FooCoalesce::$string (string) on left side of ?? is not nullable.',
				89,
			],
			[
				'Property CoalesceRule\FooCoalesce::$alwaysNull (null) on left side of ?? is always null.',
				91,
			],
			[
				'Property CoalesceRule\FooCoalesce::$string (string) on left side of ?? is not nullable.',
				93,
			],
			[
				'Static property CoalesceRule\FooCoalesce::$staticString (string) on left side of ?? is not nullable.',
				99,
			],
			[
				'Static property CoalesceRule\FooCoalesce::$staticAlwaysNull (null) on left side of ?? is always null.',
				101,
			],
			[
				'Variable $a on left side of ?? always exists and is always null.',
				115,
			],
			[
				'Property CoalesceRule\FooCoalesce::$string (string) on left side of ?? is not nullable.',
				120,
			],
			[
				'Property CoalesceRule\FooCoalesce::$alwaysNull (null) on left side of ?? is always null.',
				122,
			],
			[
				'Expression on left side of ?? is not nullable.',
				124,
			],
			[
				'Expression on left side of ?? is always null.',
				125,
			],
			[
				'Static property CoalesceRule\FooCoalesce::$staticAlwaysNull (null) on left side of ?? is always null.',
				130,
			],
			[
				'Static property CoalesceRule\FooCoalesce::$staticString (string) on left side of ?? is not nullable.',
				131,
			],
		];
		if (PHP_VERSION_ID < 80100) {
			$errors[] = [
				'Property ReflectionClass<object>::$name (class-string<object>) on left side of ?? is not nullable.',
				136,
			];
		}
		$errors[] = [
			'Variable $foo on left side of ?? is never defined.',
			141,
		];
		$errors[] = [
			'Variable $bar on left side of ?? is never defined.',
			143,
		];
		$this->analyse([__DIR__ . '/data/null-coalesce.php'], $errors);
	}

	public function testCoalesceAssignRule(): void
	{
		$this->analyse([__DIR__ . '/data/null-coalesce-assign.php'], [
			[
				'Property CoalesceAssignRule\FooCoalesce::$string (string) on left side of ??= is not nullable.',
				32,
			],
			[
				'Variable $scalar on left side of ??= always exists and is not nullable.',
				41,
			],
			[
				'Offset \'string\' on array{1, 2, 3} on left side of ??= does not exist.',
				45,
			],
			[
				'Offset \'string\' on array{array{1}, array{2}, array{3}} on left side of ??= does not exist.',
				49,
			],
			[
				'Variable $doesNotExist on left side of ??= is never defined.',
				51,
			],
			[
				'Offset \'dim\' on array{dim: 1, dim-null: 1|null, dim-null-offset: array{a: true|null}, dim-empty: array{}} on left side of ??= always exists and is not nullable.',
				67,
			],
			[
				'Offset \'dim-null-not-set\' on array{dim: 1, dim-null: 0|1, dim-null-offset: array{a: true|null}, dim-empty: array{}} on left side of ??= does not exist.',
				73,
			],
			[
				'Offset \'b\' on array{} on left side of ??= does not exist.',
				79,
			],
			[
				'Property CoalesceAssignRule\FooCoalesce::$string (string) on left side of ??= is not nullable.',
				89,
			],
			[
				'Property CoalesceAssignRule\FooCoalesce::$alwaysNull (null) on left side of ??= is always null.',
				91,
			],
			[
				'Property CoalesceAssignRule\FooCoalesce::$string (string) on left side of ??= is not nullable.',
				93,
			],
			[
				'Static property CoalesceAssignRule\FooCoalesce::$staticString (string) on left side of ??= is not nullable.',
				99,
			],
			[
				'Static property CoalesceAssignRule\FooCoalesce::$staticAlwaysNull (null) on left side of ??= is always null.',
				101,
			],
			[
				'Variable $a on left side of ??= always exists and is always null.',
				115,
			],
		]);
	}

	public function testNullsafe(): void
	{
		$this->analyse([__DIR__ . '/data/null-coalesce-nullsafe.php'], []);
	}

	public function testVariableCertaintyInNullCoalesce(): void
	{
		$this->analyse([__DIR__ . '/data/variable-certainty-null.php'], [
			[
				'Variable $scalar on left side of ?? always exists and is not nullable.',
				6,
			],
			[
				'Variable $doesNotExist on left side of ?? is never defined.',
				8,
			],
			[
				'Variable $a on left side of ?? always exists and is always null.',
				13,
			],
		]);
	}

	public function testVariableCertaintyInNullCoalesceAssign(): void
	{
		$this->analyse([__DIR__ . '/data/variable-certainty-null-assign.php'], [
			[
				'Variable $scalar on left side of ??= always exists and is not nullable.',
				6,
			],
			[
				'Variable $doesNotExist on left side of ??= is never defined.',
				8,
			],
			[
				'Variable $a on left side of ??= always exists and is always null.',
				13,
			],
		]);
	}

	public function testNullCoalesceInGlobalScope(): void
	{
		$this->analyse([__DIR__ . '/data/null-coalesce-global-scope.php'], [
			[
				'Variable $bar on left side of ?? always exists and is not nullable.',
				6,
			],
		]);
	}

	public function testBug5009(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5009.php'], []);
	}

	public function testBug5933(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5933.php'], []);
	}

	public function testBug13623(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13623.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug7109(): void
	{
		$this->analyse([__DIR__ . '/../Properties/data/bug-7109.php'], [
			[
				'Using nullsafe property access "?->aaa" on left side of ?? is unnecessary. Use -> instead.',
				17,
			],
			[
				'Using nullsafe property access "?->aaa" on left side of ?? is unnecessary. Use -> instead.',
				28,
			],
			[
				'Expression on left side of ?? is not nullable.',
				40,
			],
			[
				'Using nullsafe property access "?->aaa" on left side of ?? is unnecessary. Use -> instead.',
				66,
			],
			[
				'Expression on left side of ?? is not nullable.',
				73,
			],
		]);
	}

	public function testBug7190(): void
	{
		$this->analyse([__DIR__ . '/../Properties/data/bug-7190.php'], [
			[
				'Offset int on non-empty-array<int, int> on left side of ?? always exists and is not nullable.',
				20,
			],
		]);
	}

	public function testBug7318(): void
	{
		$this->analyse([__DIR__ . '/../Properties/data/bug-7318.php'], [
			[
				"Offset 'unique' on array{unique: bool} on left side of ?? always exists and is not nullable.",
				24,
			],
		]);
	}

	public function testBug7968(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7968.php'], []);
	}

	public function testBug8084(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8084.php'], []);
	}

	public function testBug10577(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10577.php'], []);
	}

	public function testBug11708(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11708.php'], []);
	}

	public function testBug13488(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13488.php'], []);
	}

	public function testBug13488Loose(): void
	{
		// Unlike strict comparison, loose == false / != true keep the offset
		// possibly-missing (null == false), while == true / != false imply it
		// exists, so those follow-up ?? uses are genuinely redundant.
		$this->analyse([__DIR__ . '/data/bug-13488-loose.php'], [
			[
				'Offset non-empty-string on array<string, bool> on left side of ?? always exists and is not nullable.',
				33,
			],
			[
				'Offset non-empty-string on array<string, bool> on left side of ?? always exists and is not nullable.',
				49,
			],
		]);
	}

	public function testBug10610(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10610.php'], []);
	}

	public function testBugDoctrine(): void
	{
		$this->analyse([__DIR__ . '/data/bug-doctrine.php'], []);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testBug12553(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12553.php'], []);
	}

	public function testBugMultiDimLoop(): void
	{
		$this->analyse([__DIR__ . '/data/bug-nullCoalesceMultiDimLoop.php'], []);
	}

	public function testIssetAfterRememberedConstructor(): void
	{
		$this->analyse([__DIR__ . '/data/isset-after-remembered-constructor.php'], [
			[
				'Property IssetOrCoalesceOnNonNullableInitializedProperty\User::$string on left side of ?? is not nullable nor uninitialized.',
				46,
			],
		]);
	}

	public function testPr4372(): void
	{
		$this->analyse([__DIR__ . '/data/pr-4372-null-coalesce.php'], []);
	}

	public function testBug14213(): void
	{
		$errors = [];
		if (PHP_VERSION_ID >= 80100) {
			// This is only detected with FiberScope.
			$errors[] = [
				'Coalesce operator ?? is unnecessary because the left side is always set and the right side is null.',
				21,
			];
		}
		$errors[] = [
			'Variable $x1 on left side of ?? always exists and is always null.',
			22,
		];

		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-14213.php'], $errors);
	}

	public function testBug11488(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11488.php'], []);
	}

	public function testBug13921(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13921.php'], [
			[
				'Offset 0 on non-empty-list<array<string|null>> on left side of ?? always exists and is not nullable.',
				19,
			],
		]);
	}

	public function testBug4846(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4846.php'], [
			[
				'Property Bug4846\Foo::$alwaysString (string) on left side of ?? is not nullable.',
				13,
			],
		]);
	}

	public function testBug14458(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14458.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14555(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14555.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14459(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14459.php'], [
			[
				'Property Bug14459\Dto::$policyholderId (stdClass) on left side of ?? is not nullable.',
				34,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testBug14459Hooked(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14459-hooked.php'], [
			[
				'Property Bug14459Hooked\DtoHooked::$policyholderId (stdClass) on left side of ?? is not nullable.',
				21,
			],
		]);
	}

	public function testBug4337(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4337.php'], [
			[
				'Coalesce operator ?? is unnecessary because the left side is always set and the right side is null.',
				37,
			],
			[
				'Coalesce operator ?? is unnecessary because the left side is always set and the right side is null.',
				42,
			],
			[
				'Coalesce operator ?? is unnecessary because the left side is always set and the right side is null.',
				47,
			],
			[
				'Coalesce operator ?? is unnecessary because the left side is always set and the right side is null.',
				53,
			],
			[
				'Coalesce operator ?? is unnecessary because the left side is always set and the right side is null.',
				58,
			],
			[
				'Coalesce operator ?? is unnecessary because the left side is always set and the right side is null.',
				63,
			],
			[
				'Coalesce operator ?? is unnecessary because the left side is always set and the right side is null.',
				69,
			],
			[
				'Coalesce operator ??= is unnecessary because the left side is always set and the right side is null.',
				75,
			],
		]);
	}

	public function testBug12179(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12179.php'], [
			[
				'Coalesce operator ?? is unnecessary because the left side is always set and the right side is null.',
				8,
			],
			[
				'Coalesce operator ?? is unnecessary because the left side is always set and the right side is null.',
				24,
			],
		]);
	}

	public function testBug9966(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9966.php'], [
			[
				'Offset \'key1\' on array{key1: string, key2: string|null, key3?: string, key4?: string|null} on left side of ?? always exists and is not nullable.',
				9,
			],
			[
				'Coalesce operator ?? is unnecessary because the left side is always set and the right side is null.',
				12,
			],
		]);
	}

	public function testBug14393(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14393.php'], [
			[
				'Property Bug14393\MyClass::$i (int) on left side of ?? is not nullable.',
				11,
			],
			[
				'Property Bug14393\MyClassPhpDoc::$i (int) on left side of ?? is not nullable.',
				36,
			],
			[
				'Property Bug14393\MyClass::$i (int) on left side of ?? is not nullable.',
				80,
			],
			[
				'Property Bug14393\MyClassPhpDoc::$i (int) on left side of ?? is not nullable.',
				92,
			],
			[
				'Static property Bug14393\MyClassStatic::$i (int) on left side of ?? is not nullable.',
				120,
			],
			[
				'Static property Bug14393\MyClassStatic::$i (int) on left side of ?? is not nullable.',
				150,
			],
			[
				'Variable $undefinedVar on left side of ?? is never defined.',
				164,
			],
		]);
	}

	public function testBug15021(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15021.php'], []);
	}

	public function testNullCoalesceAssignRightSideScope(): void
	{
		$this->analyse([__DIR__ . '/data/null-coalesce-assign-right-side-scope.php'], [
			[
				'Property NullCoalesceAssignRightSideScope\Foo::$nonNullable (string) on left side of ??= is not nullable.',
				19,
			],
			[
				'Property NullCoalesceAssignRightSideScope\Foo::$nonNullable (string) on left side of ?? is not nullable.',
				19,
			],
			[
				'Static property NullCoalesceAssignRightSideScope\Foo::$staticNonNullable (string) on left side of ??= is not nullable.',
				24,
			],
			[
				'Static property NullCoalesceAssignRightSideScope\Foo::$staticNonNullable (string) on left side of ?? is not nullable.',
				24,
			],
			[
				'Variable $undefined on left side of ??= is never defined.',
				46,
			],
			[
				'Variable $undefined on left side of ?? is never defined.',
				46,
			],
			[
				'Offset \'foo\' on array{bar?: string} on left side of ??= does not exist.',
				89,
			],
		]);
	}

	public function testBug15056(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15056.php'], []);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testPropertyInitializationCustomSerialization(): void
	{
		$this->analyse([__DIR__ . '/data/property-initialization-custom-serialization.php'], [
			[
				'Property PropertyInitializationCustomSerialization\NoSerialization::$string on left side of ?? is not nullable nor uninitialized.',
				20,
			],
			[
				'Property PropertyInitializationCustomSerialization\OnlyWakeup::$string on left side of ?? is not nullable nor uninitialized.',
				41,
			],
			[
				'Property PropertyInitializationCustomSerialization\PromotedNoSerialization::$string on left side of ?? is not nullable nor uninitialized.',
				238,
			],
			[
				'Property PropertyInitializationCustomSerialization\CoalesceAssignNoSerialization::$string on left side of ??= is not nullable nor uninitialized.',
				271,
			],
		]);
	}

	public function testBug15046(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15046.php'], [
			[
				'Property Bug15046\\BothBranches::$answer on left side of ?? is not nullable nor uninitialized.',
				140,
			],
			[
				'Property Bug15046\\ConditionMetAgain::$answer (int) on left side of ?? is not nullable.',
				157,
			],
		]);
	}

	public function testInTrait(): void
	{
		$this->analyse([__DIR__ . '/data/isset-in-trait.php'], [
			[
				'Property IssetInTrait\FirstNonNullableProperty::$k (int<1, max>) on left side of ?? is not nullable.',
				82,
			],
			[
				'Property IssetInTrait\SecondNonNullableProperty::$k (int<1, max>) on left side of ?? is not nullable.',
				82,
			],
			[
				'Variable $s on left side of ?? always exists and is not nullable.',
				119,
			],
		]);
	}

}

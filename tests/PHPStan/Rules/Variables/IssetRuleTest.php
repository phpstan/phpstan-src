<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<IssetRule>
 */
class IssetRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new IssetRule(new IssetCheck(
			new PropertyDescriptor(),
			true,
			$this->treatPhpDocTypesAsCertain,
		));
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/isset.php'], [
			[
				'Property IssetRule\FooCoalesce::$string (string) in isset() is not nullable.',
				32,
			],
			[
				'Variable $scalar in isset() always exists and is not nullable.',
				41,
			],
			[
				'Offset \'string\' on array{1, 2, 3} in isset() does not exist.',
				45,
			],
			[
				'Offset \'string\' on array{array{1}, array{2}, array{3}} in isset() does not exist.',
				49,
			],
			[
				'Variable $doesNotExist in isset() is never defined.',
				51,
			],
			[
				'Offset \'dim\' on array{dim: 1, dim-null: 1|null, dim-null-offset: array{a: true|null}, dim-empty: array{}} in isset() always exists and is not nullable.',
				67,
			],
			[
				'Offset \'dim-null-not-set\' on array{dim: 1, dim-null: 1|null, dim-null-offset: array{a: true|null}, dim-empty: array{}} in isset() does not exist.',
				73,
			],
			[
				'Offset \'b\' on array{} in isset() does not exist.',
				79,
			],
			[
				'Property IssetRule\FooCoalesce::$string (string) in isset() is not nullable.',
				85,
			],
			[
				'Property IssetRule\FooCoalesce::$alwaysNull (null) in isset() is always null.',
				87,
			],
			[
				'Property IssetRule\FooCoalesce::$string (string) in isset() is not nullable.',
				89,
			],
			[
				'Static property IssetRule\FooCoalesce::$staticString (string) in isset() is not nullable.',
				95,
			],
			[
				'Static property IssetRule\FooCoalesce::$staticAlwaysNull (null) in isset() is always null.',
				97,
			],
			[
				'Variable $a in isset() always exists and is always null.',
				111,
			],
			[
				'Property IssetRule\FooCoalesce::$string (string) in isset() is not nullable.',
				116,
			],
			[
				'Property IssetRule\FooCoalesce::$alwaysNull (null) in isset() is always null.',
				118,
			],
			[
				'Static property IssetRule\FooCoalesce::$staticAlwaysNull (null) in isset() is always null.',
				123,
			],
			[
				'Static property IssetRule\FooCoalesce::$staticString (string) in isset() is not nullable.',
				124,
			],
			[
				'Offset \'foo\' on array{foo: string} in isset() always exists and is not nullable.',
				170,
			],
			[
				'Offset \'bar\' on array{bar: 1} in isset() always exists and is not nullable.',
				173,
			],
		]);
	}

	public function testRuleWithoutTreatPhpDocTypesAsCertain(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/isset.php'], [
			[
				'Property IssetRule\FooCoalesce::$string (string) in isset() is not nullable.',
				32,
			],
			[
				'Variable $scalar in isset() always exists and is not nullable.',
				41,
			],
			[
				'Offset \'string\' on array{1, 2, 3} in isset() does not exist.',
				45,
			],
			[
				'Offset \'string\' on array{array{1}, array{2}, array{3}} in isset() does not exist.',
				49,
			],
			[
				'Variable $doesNotExist in isset() is never defined.',
				51,
			],
			[
				'Offset \'dim\' on array{dim: 1, dim-null: 1|null, dim-null-offset: array{a: true|null}, dim-empty: array{}} in isset() always exists and is not nullable.',
				67,
			],
			[
				'Offset \'dim-null-not-set\' on array{dim: 1, dim-null: 1|null, dim-null-offset: array{a: true|null}, dim-empty: array{}} in isset() does not exist.',
				73,
			],
			[
				'Offset \'b\' on array{} in isset() does not exist.',
				79,
			],
			[
				'Property IssetRule\FooCoalesce::$string (string) in isset() is not nullable.',
				85,
			],
			[
				'Property IssetRule\FooCoalesce::$alwaysNull (null) in isset() is always null.',
				87,
			],
			[
				'Property IssetRule\FooCoalesce::$string (string) in isset() is not nullable.',
				89,
			],
			[
				'Static property IssetRule\FooCoalesce::$staticString (string) in isset() is not nullable.',
				95,
			],
			[
				'Static property IssetRule\FooCoalesce::$staticAlwaysNull (null) in isset() is always null.',
				97,
			],
			[
				'Variable $a in isset() always exists and is always null.',
				111,
			],
			[
				'Property IssetRule\FooCoalesce::$string (string) in isset() is not nullable.',
				116,
			],
			[
				'Property IssetRule\FooCoalesce::$alwaysNull (null) in isset() is always null.',
				118,
			],
			[
				'Static property IssetRule\FooCoalesce::$staticAlwaysNull (null) in isset() is always null.',
				123,
			],
			[
				'Static property IssetRule\FooCoalesce::$staticString (string) in isset() is not nullable.',
				124,
			],
		]);
	}

	public function testNativePropertyTypes(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/isset-native-property-types.php'], [
			[
				'Property IssetNativePropertyTypes\Foo::$hasDefaultValue (int) in isset() is not nullable.',
				17,
			],
			[
				'Property IssetNativePropertyTypes\Foo::$isAssignedBefore (int) in isset() is not nullable.',
				20,
			],
		]);
	}

	public function testBug4290(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4290.php'], []);
	}

	public function testBug4671(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4671.php'], [
			[
				'Offset decimal-int-string on array<string, string> in isset() does not exist.',
				13,
			],
		]);
	}

	public function testVariableCertaintyInIsset(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/variable-certainty-isset.php'], [
			[
				'Variable $alwaysDefinedNotNullable in isset() always exists and is not nullable.',
				14,
			],
			[
				'Variable $neverDefinedVariable in isset() is never defined.',
				22,
			],
			[
				'Variable $anotherNeverDefinedVariable in isset() is never defined.',
				42,
			],
			[
				'Variable $yetAnotherNeverDefinedVariable in isset() is never defined.',
				46,
			],
			[
				'Variable $yetYetAnotherNeverDefinedVariableInIsset in isset() is never defined.',
				56,
			],
			[
				'Variable $anotherVariableInDoWhile in isset() always exists and is not nullable.',
				104,
			],
			[
				'Variable $variableInSecondCase in isset() is never defined.',
				110,
			],
			[
				'Variable $variableInFirstCase in isset() always exists and is not nullable.',
				112,
			],
			[
				// could be Variable $variableInFirstCase in isset() always exists and is not nullable.
				'Variable $variableInFirstCase in isset() is never defined.',
				116,
			],
			[
				// could be Variable $variableInSecondCase in isset() always exists and is not nullable.
				'Variable $variableInSecondCase in isset() is never defined.',
				117,
			],
			[
				'Variable $variableAssignedInSecondCase in isset() is never defined.',
				119,
			],
			[
				'Variable $alwaysDefinedForSwitchCondition in isset() always exists and is not nullable.',
				139,
			],
			[
				'Variable $alwaysDefinedForCaseNodeCondition in isset() always exists and is not nullable.',
				140,
			],
			[
				'Variable $alwaysDefinedNotNullable in isset() always exists and is not nullable.',
				152,
			],
			[
				'Variable $neverDefinedVariable in isset() is never defined.',
				152,
			],
			[
				'Variable $a in isset() always exists and is not nullable.',
				214,
			],
			[
				'Variable $null in isset() always exists and is always null.',
				225,
			],
		]);
	}

	public function testIssetInGlobalScope(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/isset-global-scope.php'], [
			[
				'Variable $alwaysDefinedNotNullable in isset() always exists and is not nullable.',
				8,
			],
		]);
	}

	public function testNullsafe(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/isset-nullsafe.php'], [
			[
				'Using nullsafe property access "?->bla" in isset() is unnecessary. Use -> instead.',
				10,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug7109(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/../Properties/data/bug-7109.php'], [
			[
				'Using nullsafe property access "?->aaa" in isset() is unnecessary. Use -> instead.',
				18,
			],
			[
				'Using nullsafe property access "?->aaa" in isset() is unnecessary. Use -> instead.',
				29,
			],
			[
				'Expression in isset() is not nullable.',
				41,
			],
			[
				'Using nullsafe property access "?->aaa" in isset() is unnecessary. Use -> instead.',
				67,
			],
			[
				'Expression in isset() is not nullable.',
				74,
			],
		]);
	}

	public function testBug7318(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/../Properties/data/bug-7318.php'], [
			[
				"Offset 'unique' on array{unique: bool} in isset() always exists and is not nullable.",
				27,
			],
		]);
	}

	public function testBug6163(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-6163.php'], [
			[
				'Offset \'123\' on array{123: true, abc: true} in isset() always exists and is not nullable.',
				11,
			],
		]);
	}

	public function testBug6997(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-6997.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug7776(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-7776.php'], []);
	}

	public function testBug6008(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-6008.php'], []);
	}

	public function testBug7292(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-7292.php'], []);
	}

	public function testObjectShapes(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		// could be checked but current is not
		$this->analyse([__DIR__ . '/data/isset-object-shapes.php'], []);
	}

	public function testBug10151(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-10151.php'], []);
	}

	public function testBug3985(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-3985.php'], [
			[
				'Variable $foo in isset() is never defined.',
				13,
			],
			[
				'Variable $foo in isset() is never defined.',
				21,
			],
		]);
	}

	public function testBug10064(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-10064.php'], []);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testVirtualProperty(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/isset-virtual-property.php'], [
			[
				'Property IssetVirtualProperty\Example::$noon (DateTimeImmutable) in isset() is not nullable.',
				16,
			],
		]);
	}

	public function testBug9328(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-9328.php'], []);
	}

	public function testBug12771(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-12771.php'], []);
	}

	public function testBug11708(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-11708.php'], []);
	}

	public function testBug13488(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-13488.php'], []);
	}

	public function testBug13488Loose(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		// Unlike strict comparison, loose == false / != true keep the offset
		// possibly-missing (null == false), while == true / != false imply it
		// exists, so those follow-up isset() calls are genuinely redundant.
		$this->analyse([__DIR__ . '/data/bug-13488-loose.php'], [
			[
				'Offset non-empty-string on array<string, bool> in isset() always exists and is not nullable.',
				32,
			],
			[
				'Offset non-empty-string on array<string, bool> in isset() always exists and is not nullable.',
				48,
			],
		]);
	}

	public function testIssetAfterRememberedConstructor(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/isset-after-remembered-constructor.php'], [
			[
				'Property IssetOrCoalesceOnNonNullableInitializedProperty\User::$string in isset() is not nullable nor uninitialized.',
				34,
			],
		]);
	}

	#[RequiresPhp('>= 8.2')]
	public function testPropertyInitializationCustomSerialization(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/property-initialization-custom-serialization.php'], [
			[
				'Property PropertyInitializationCustomSerialization\NoSerialization::$string in isset() is not nullable nor uninitialized.',
				21,
			],
			[
				'Property PropertyInitializationCustomSerialization\OnlyWakeup::$string in isset() is not nullable nor uninitialized.',
				42,
			],
		]);
	}

	#[RequiresPhp('>= 8.2')]
	public function testPropertyInitializationUnset(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/property-initialization-unset.php'], [
			[
				'Property PropertyInitializationUnset\NoUnset::$string in isset() is not nullable nor uninitialized.',
				19,
			],
		]);
	}

	public function testPr4374(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/pr-4374.php'], [
			[
				'Offset string on non-empty-array<PR4374\Foo> in isset() always exists and is not nullable.',
				23,
			],
		]);
	}

	public function testIssetConstantArray(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/isset-constant-array.php'], [
			[
				'Offset 2 on array{0: string, 1: string, 2: string, 3: string, 4?: string} in isset() always exists and is not nullable.',
				13,
			],
			[
				'Offset 3 on array{string, string, string, string, string} in isset() always exists and is not nullable.',
				17,
			],
		]);
	}

	public function testBug10640(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-10640.php'], []);
	}

	public function testBug9503(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-9503.php'], []);
	}

	public function testBug14555(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-14555.php'], []);
	}

	public function testBug14393(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/bug-14393.php'], [
			[
				'Property Bug14393\MyClass::$i (int) in isset() is not nullable.',
				12,
			],
			[
				'Property Bug14393\MyClassPhpDoc::$i (int) in isset() is not nullable.',
				37,
			],
			[
				'Property Bug14393\MyClass::$i (int) in isset() is not nullable.',
				81,
			],
			[
				'Property Bug14393\MyClassPhpDoc::$i (int) in isset() is not nullable.',
				93,
			],
			[
				'Static property Bug14393\MyClassStatic::$i (int) in isset() is not nullable.',
				121,
			],
			[
				'Static property Bug14393\MyClassStatic::$i (int) in isset() is not nullable.',
				151,
			],
			[
				'Variable $undefinedVar in isset() is never defined.',
				165,
			],
		]);
	}

	public function testBug14610(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-14610.php'], []);
	}

	public function testNullCoalesceAssignRightSideScope(): void
	{
		$this->treatPhpDocTypesAsCertain = true;

		$this->analyse([__DIR__ . '/data/null-coalesce-assign-right-side-scope.php'], []);
	}

}

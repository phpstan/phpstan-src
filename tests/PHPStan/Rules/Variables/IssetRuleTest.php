<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<IssetRule>
 */
class IssetRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	private bool $strictUnnecessaryNullsafePropertyFetch;

	protected function getRule(): Rule
	{
		return new IssetRule(new IssetCheck(
			new PropertyDescriptor(),
			new PropertyReflectionFinder(),
			true,
			$this->treatPhpDocTypesAsCertain,
			$this->strictUnnecessaryNullsafePropertyFetch,
		));
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
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
				'Offset \'dim-null-not-set\' on array{dim: 1, dim-null-offset: array{a: true|null}, dim-empty: array{}, dim-null: 1|null} in isset() does not exist.',
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
		$this->strictUnnecessaryNullsafePropertyFetch = false;
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
				'Offset \'dim-null-not-set\' on array{dim: 1, dim-null-offset: array{a: true|null}, dim-empty: array{}, dim-null: 1|null} in isset() does not exist.',
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
		$this->strictUnnecessaryNullsafePropertyFetch = false;
		$this->analyse([__DIR__ . '/data/isset-native-property-types.php'], [
			/*[
				// no way to achieve this with current PHP Reflection API
				// There's ReflectionClass::getDefaultProperties()
				// but it cannot differentiate between `public int $foo` and `public int $foo = null`;
				'Property IssetNativePropertyTypes\Foo::$hasDefaultValue (int) in isset() is not nullable.',
				17,
			],*/
			[
				'Property IssetNativePropertyTypes\Foo::$isAssignedBefore (int) in isset() is not nullable.',
				20,
			],
		]);
	}

	public function testBug4290(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
		$this->analyse([__DIR__ . '/data/bug-4290.php'], []);
	}

	public function testBug4671(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
		$this->analyse([__DIR__ . '/data/bug-4671.php'], [[
			'Offset numeric-string on array<string, string> in isset() does not exist.',
			13,
		]]);
	}

	public function testVariableCertaintyInIsset(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
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
				'Variable $variableInFirstCase in isset() always exists and is not nullable.',
				116,
			],
			[
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
		$this->strictUnnecessaryNullsafePropertyFetch = false;
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
		$this->strictUnnecessaryNullsafePropertyFetch = false;
		$this->analyse([__DIR__ . '/data/isset-nullsafe.php'], []);
	}

	public function testBug7109(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		$this->analyse([__DIR__ . '/../Properties/data/bug-7109.php'], [
			[
				'Expression in isset() is not nullable.',
				41,
			],
		]);
	}

	public function testBug7109Strict(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

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
				'Using nullsafe property access "?->(Expression)" in isset() is unnecessary. Use -> instead.',
				74,
			],
		]);
	}

	public function testBug7318(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

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
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/data/bug-6163.php'], []);
	}

	public function testBug6997(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/data/bug-6997.php'], []);
	}

	public function testBug7776(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		$this->analyse([__DIR__ . '/../../Analyser/data/bug-7776.php'], []);
	}

	public function testBug6008(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/data/bug-6008.php'], []);
	}

	public function testBug7292(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/data/bug-7292.php'], []);
	}

	public function testObjectShapes(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		// could be checked but current is not
		$this->analyse([__DIR__ . '/data/isset-object-shapes.php'], []);
	}

	public function testBug10151(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/data/bug-10151.php'], []);
	}

	public function testBug3985(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/../../Analyser/data/bug-3985.php'], [
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
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/data/bug-10064.php'], []);
	}

}

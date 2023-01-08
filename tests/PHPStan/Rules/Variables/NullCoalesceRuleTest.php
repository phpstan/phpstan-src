<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<NullCoalesceRule>
 */
class NullCoalesceRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	private bool $strictUnnecessaryNullsafePropertyFetch;

	protected function getRule(): Rule
	{
		return new NullCoalesceRule(new IssetCheck(
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

	public function testCoalesceRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
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
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
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
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
		$this->analyse([__DIR__ . '/data/null-coalesce-nullsafe.php'], []);
	}

	public function testVariableCertaintyInNullCoalesce(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
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
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
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
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
		$this->analyse([__DIR__ . '/data/null-coalesce-global-scope.php'], [
			[
				'Variable $bar on left side of ?? always exists and is not nullable.',
				6,
			],
		]);
	}

	public function testBug5933(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;
		$this->analyse([__DIR__ . '/data/bug-5933.php'], []);
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
				'Expression on left side of ?? is not nullable.',
				40,
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
				'Using nullsafe property access "?->(Expression)" on left side of ?? is unnecessary. Use -> instead.',
				73,
			],
		]);
	}

	public function testBug7190(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = false;

		$this->analyse([__DIR__ . '/../Properties/data/bug-7190.php'], [
			[
				'Offset int on array<int, int> on left side of ?? always exists and is not nullable.',
				20,
			],
		]);
	}

	public function testBug7318(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/../Properties/data/bug-7318.php'], [
			[
				"Offset 'unique' on array{unique: bool} on left side of ?? always exists and is not nullable.",
				24,
			],
		]);
	}

	public function testBug7968(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/data/bug-7968.php'], []);
	}

	public function testBug8084(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->strictUnnecessaryNullsafePropertyFetch = true;

		$this->analyse([__DIR__ . '/data/bug-8084.php'], []);
	}

}

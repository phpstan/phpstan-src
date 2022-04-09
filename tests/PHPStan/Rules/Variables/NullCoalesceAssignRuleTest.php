<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<NullCoalesceAssignRule>
 */
class NullCoalesceAssignRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new NullCoalesceAssignRule(new IssetCheck(
			new PropertyDescriptor(),
			new PropertyReflectionFinder(),
			true,
			$this->treatPhpDocTypesAsCertain,
		));
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testCoalesceAssignRule(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->treatPhpDocTypesAsCertain = true;
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

	public function testVariableCertaintyInNullCoalesceAssign(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->treatPhpDocTypesAsCertain = true;
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

}

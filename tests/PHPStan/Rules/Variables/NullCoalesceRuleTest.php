<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;

/**
 * @extends \PHPStan\Testing\RuleTestCase<NullCoalesceRule>
 */
class NullCoalesceRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new NullCoalesceRule(new IssetCheck(new PropertyDescriptor(), new PropertyReflectionFinder(), true));
	}

	public function testCoalesceRule(): void
	{
		$this->analyse([__DIR__ . '/data/null-coalesce.php'], [
			[
				'Property CoalesceRule\FooCoalesce::$string (string) on left side of ?? is not nullable.',
				32,
			],
			[
				'Variable $scalar on left side of ?? always exists and is not nullable.',
				41,
			],
			[
				'Offset \'string\' on array(1, 2, 3) on left side of ?? does not exist.',
				45,
			],
			[
				'Offset \'string\' on array(array(1), array(2), array(3)) on left side of ?? does not exist.',
				49,
			],
			[
				'Variable $doesNotExist on left side of ?? is never defined.',
				51,
			],
			[
				'Offset \'dim\' on array(\'dim\' => 1, \'dim-null\' => 1|null, \'dim-null-offset\' => array(\'a\' => true|null), \'dim-empty\' => array()) on left side of ?? always exists and is not nullable.',
				67,
			],
			[
				'Offset \'b\' on array() on left side of ?? does not exist.',
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
			[
				'Property ReflectionClass<object>::$name (class-string<object>) on left side of ?? is not nullable.',
				136,
			],
		]);
	}

	public function testCoalesceAssignRule(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

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
				'Offset \'string\' on array(1, 2, 3) on left side of ??= does not exist.',
				45,
			],
			[
				'Offset \'string\' on array(array(1), array(2), array(3)) on left side of ??= does not exist.',
				49,
			],
			[
				'Variable $doesNotExist on left side of ??= is never defined.',
				51,
			],
			[
				'Offset \'dim\' on array(\'dim\' => 1, \'dim-null\' => 1|null, \'dim-null-offset\' => array(\'a\' => true|null), \'dim-empty\' => array()) on left side of ??= always exists and is not nullable.',
				67,
			],
			[
				'Offset \'b\' on array() on left side of ??= does not exist.',
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
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

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
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

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

}

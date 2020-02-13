<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;

/**
 * @extends \PHPStan\Testing\RuleTestCase<CoalesceRule>
 */
class CoalesceRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new CoalesceRule(new PropertyDescriptor(), new PropertyReflectionFinder());
	}

	public function testCoalesceRule(): void
	{
		require_once __DIR__ . '/data/coalesce.php';
		$this->analyse([__DIR__ . '/data/coalesce.php'], [
			[
				'Coalesce of Property CoalesceRule\FooCoalesce::$string (string), which cannot be null.',
				32,
			],
			[
				'Coalesce of variable $scalar, which cannot be null.',
				41,
			],
			[
				'Coalesce of invalid offset \'string\' on array(1, 2, 3).',
				45,
			],
			[
				'Coalesce of invalid offset \'string\' on array(array(1), array(2), array(3)).',
				49,
			],
			[
				'Coalesce of undefined variable $doesNotExist.',
				51,
			],
			[
				'Coalesce of offset \'dim\' on array(\'dim\' => 1, \'dim-null\' => 1|null, \'dim-null-offset\' => array(\'a\' => true|null), \'dim-empty\' => array()), which cannot be null.',
				67,
			],
			[
				'Coalesce of invalid offset \'b\' on array().',
				79,
			],
			[
				'Coalesce of return value for call to \'rand\', which cannot be null.',
				81,
			],
			[
				'Coalesce of Property CoalesceRule\FooCoalesce::$string (string), which cannot be null.',
				89,
			],
			[
				'Coalesce of Property CoalesceRule\FooCoalesce::$alwaysNull (null), which is always null.',
				91,
			],
			[
				'Coalesce of Property CoalesceRule\FooCoalesce::$string (string), which cannot be null.',
				93,
			],
			[
				'Coalesce of Static property CoalesceRule\FooCoalesce::$staticString (string), which cannot be null.',
				99,
			],
			[
				'Coalesce of Static property CoalesceRule\FooCoalesce::$staticAlwaysNull (null), which is always null.',
				101,
			],
			[
				'Coalesce of variable $a, which is always null.',
				115,
			],
		]);
	}

	public function testCoalesceAssignRule(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		require_once __DIR__ . '/data/coalesce-assign.php';
		$this->analyse([__DIR__ . '/data/coalesce-assign.php'], [
			[
				'Null-coalescing assignment of Property CoalesceAssignRule\FooCoalesce::$string (string), which cannot be null.',
				32,
			],
			[
				'Null-coalescing assignment of variable $scalar, which cannot be null.',
				41,
			],
			[
				'Null-coalescing assignment of invalid offset \'string\' on array(1, 2, 3).',
				45,
			],
			[
				'Null-coalescing assignment of invalid offset \'string\' on array(array(1), array(2), array(3)).',
				49,
			],
			[
				'Null-coalescing assignment of undefined variable $doesNotExist.',
				51,
			],
			[
				'Null-coalescing assignment of offset \'dim\' on array(\'dim\' => 1, \'dim-null\' => 1|null, \'dim-null-offset\' => array(\'a\' => true|null), \'dim-empty\' => array()), which cannot be null.',
				67,
			],
			[
				'Null-coalescing assignment of invalid offset \'b\' on array().',
				79,
			],
			[
				'Null-coalescing assignment of Property CoalesceAssignRule\FooCoalesce::$string (string), which cannot be null.',
				89,
			],
			[
				'Null-coalescing assignment of Property CoalesceAssignRule\FooCoalesce::$alwaysNull (null), which is always null.',
				91,
			],
			[
				'Null-coalescing assignment of Property CoalesceAssignRule\FooCoalesce::$string (string), which cannot be null.',
				93,
			],
			[
				'Null-coalescing assignment of Static property CoalesceAssignRule\FooCoalesce::$staticString (string), which cannot be null.',
				99,
			],
			[
				'Null-coalescing assignment of Static property CoalesceAssignRule\FooCoalesce::$staticAlwaysNull (null), which is always null.',
				101,
			],
			[
				'Null-coalescing assignment of variable $a, which is always null.',
				115,
			],
		]);
	}

}

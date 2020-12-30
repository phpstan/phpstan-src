<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IssetRule>
 */
class IssetRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IssetRule(new IssetCheck(new PropertyDescriptor(), new PropertyReflectionFinder()));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/isset.php'], [
			[
				'Property IssetRule\FooCoalesce::$string (string) in isset() is not nullable.',
				32,
			],
			[
				'Offset \'string\' on array(1, 2, 3) in isset() does not exist.',
				45,
			],
			[
				'Offset \'string\' on array(array(1), array(2), array(3)) in isset() does not exist.',
				49,
			],
			[
				'Offset \'dim\' on array(\'dim\' => 1, \'dim-null\' => 1|null, \'dim-null-offset\' => array(\'a\' => true|null), \'dim-empty\' => array()) in isset() always exists and is not nullable.',
				67,
			],
			[
				'Offset \'b\' on array() in isset() does not exist.',
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
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
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
		$this->analyse([__DIR__ . '/data/bug-4290.php'], [

		]);
	}

}

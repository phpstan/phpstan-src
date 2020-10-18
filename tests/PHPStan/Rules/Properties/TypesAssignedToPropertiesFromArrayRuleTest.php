<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<TypesAssignedToPropertiesRule>
 */
class TypesAssignedToPropertiesFromArrayRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new TypesAssignedToPropertiesRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false), new PropertyDescriptor(), new PropertyReflectionFinder());
	}

	public function testTypesAssignedToProperties(): void
	{
		$this->analyse([__DIR__ . '/data/properties-from-array-into-object.php'], [
			[
				'Property PropertiesFromArrayIntoObject\Foo::$lall (int) does not accept string.',
				42,
			],
			[
				'Property PropertiesFromArrayIntoObject\Foo::$lall (int) does not accept string.',
				54,
			],
			[
				'Property PropertiesFromArrayIntoObject\Foo::$test (int|null) does not accept stdClass.',
				66
			],
			[
				'Property PropertiesFromArrayIntoObject\Foo::$lall (int) does not accept string.',
				69,
			],
			[
				'Property PropertiesFromArrayIntoObject\Foo::$foo (string) does not accept int.',
				73,
			],
			[
				'Property PropertiesFromArrayIntoObject\Foo::$foo (string) does not accept float.',
				83,
			],
			[
				'Property PropertiesFromArrayIntoObject\Foo::$lall (int) does not accept string.',
				110,
			],
			[
				'Property PropertiesFromArrayIntoObject\FooBar::$foo (string) does not accept float.',
				147
			]
		]);
	}

	public function testTypesAssignedToStaticProperties(): void
	{
		$this->analyse([__DIR__ . '/data/properties-from-array-into-static-object.php'], [
			[
				'Static property PropertiesFromArrayIntoStaticObject\Foo::$lall (stdClass|null) does not accept string.',
				29,
			],
			[
				'Static property PropertiesFromArrayIntoStaticObject\Foo::$foo (string) does not accept float.',
				36
			],
			[
				'Static property PropertiesFromArrayIntoStaticObject\FooBar::$foo (string) does not accept float.',
				72
			]
		]);
	}

}

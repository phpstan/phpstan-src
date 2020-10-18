<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<TypesAssignedToPropertiesRule>
 */
class TypesAssignedToPropertiesRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new TypesAssignedToPropertiesRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false), new PropertyDescriptor(), new PropertyReflectionFinder());
	}

	public function testTypesAssignedToProperties(): void
	{
		$this->analyse([__DIR__ . '/data/properties-assigned-types.php'], [
			[
				'Property PropertiesAssignedTypes\Foo::$stringProperty (string) does not accept int.',
				29,
			],
			[
				'Property PropertiesAssignedTypes\Foo::$intProperty (int) does not accept string.',
				31,
			],
			[
				'Property PropertiesAssignedTypes\Foo::$fooProperty (PropertiesAssignedTypes\Foo) does not accept PropertiesAssignedTypes\Bar.',
				33,
			],
			[
				'Static property PropertiesAssignedTypes\Foo::$staticStringProperty (string) does not accept int.',
				35,
			],
			[
				'Static property PropertiesAssignedTypes\Foo::$staticStringProperty (string) does not accept int.',
				37,
			],
			[
				'Property PropertiesAssignedTypes\Ipsum::$parentStringProperty (string) does not accept int.',
				39,
			],
			[
				'Property PropertiesAssignedTypes\Foo::$unionPropertySelf (array<PropertiesAssignedTypes\Foo>|(iterable<PropertiesAssignedTypes\Foo>&PropertiesAssignedTypes\Collection)) does not accept PropertiesAssignedTypes\Foo.',
				44,
			],
			[
				'Property PropertiesAssignedTypes\Foo::$unionPropertySelf (array<PropertiesAssignedTypes\Foo>|(iterable<PropertiesAssignedTypes\Foo>&PropertiesAssignedTypes\Collection)) does not accept array<int, PropertiesAssignedTypes\Bar>.',
				45,
			],
			[
				'Property PropertiesAssignedTypes\Foo::$unionPropertySelf (array<PropertiesAssignedTypes\Foo>|(iterable<PropertiesAssignedTypes\Foo>&PropertiesAssignedTypes\Collection)) does not accept PropertiesAssignedTypes\Bar.',
				46,
			],
			[
				'Property PropertiesAssignedTypes\Ipsum::$parentStringProperty (string) does not accept int.',
				48,
			],
			[
				'Static property PropertiesAssignedTypes\Ipsum::$parentStaticStringProperty (string) does not accept int.',
				50,
			],
			[
				'Property PropertiesAssignedTypes\Foo::$intProperty (int) does not accept string.',
				60,
			],
			[
				'Property PropertiesAssignedTypes\Ipsum::$foo (PropertiesAssignedTypes\Ipsum) does not accept PropertiesAssignedTypes\Bar.',
				143,
			],
			[
				'Static property PropertiesAssignedTypes\Ipsum::$fooStatic (PropertiesAssignedTypes\Ipsum) does not accept PropertiesAssignedTypes\Bar.',
				144,
			],
		]);
	}

	public function testBug1216(): void
	{
		$this->analyse([__DIR__ . '/data/bug-1216.php'], [
			[
				'Property Bug1216PropertyTest\Baz::$untypedBar (string) does not accept int.',
				35,
			],
			[
				'Property Bug1216PropertyTest\Dummy::$foo (Exception) does not accept stdClass.',
				59,
			],
		]);
	}

	public function testTypesAssignedToPropertiesExpressionNames(): void
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
				66,
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
				147,
			],
		]);
	}

	public function testTypesAssignedToStaticPropertiesExpressionNames(): void
	{
		$this->analyse([__DIR__ . '/data/properties-from-array-into-static-object.php'], [
			[
				'Static property PropertiesFromArrayIntoStaticObject\Foo::$lall (stdClass|null) does not accept string.',
				29,
			],
			[
				'Static property PropertiesFromArrayIntoStaticObject\Foo::$foo (string) does not accept float.',
				36,
			],
			[
				'Static property PropertiesFromArrayIntoStaticObject\FooBar::$foo (string) does not accept float.',
				72,
			],
		]);
	}

}

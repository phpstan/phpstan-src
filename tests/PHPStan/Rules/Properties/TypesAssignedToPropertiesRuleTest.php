<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TypesAssignedToPropertiesRule>
 */
class TypesAssignedToPropertiesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
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
			[
				'Property PropertiesAssignedTypes\AssignRefFoo::$stringProperty (string) does not accept int.',
				312,
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
				'Property PropertiesFromArrayIntoObject\Foo::$float_test (float) does not accept float|int|string.',
				69,
			],
			[
				'Property PropertiesFromArrayIntoObject\Foo::$foo (string) does not accept float|int|string.',
				69,
			],
			[
				'Property PropertiesFromArrayIntoObject\Foo::$lall (int) does not accept float|int|string.',
				69,
			],
			[
				'Property PropertiesFromArrayIntoObject\Foo::$foo (string) does not accept (float|int).',
				73,
			],
			[
				'Property PropertiesFromArrayIntoObject\Foo::$foo (string) does not accept float.',
				83,
			],
			[
				'Property PropertiesFromArrayIntoObject\Foo::$foo (string) does not accept float|int|string.',
				97,
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

	public function testBug3777(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3777.php'], [
			[
				'Property Bug3777\Bar::$foo (Bug3777\Foo<stdClass>) does not accept Bug3777\Fooo<object>.',
				58,
			],
			[
				'Property Bug3777\Ipsum::$ipsum (Bug3777\Lorem<stdClass, Exception>) does not accept Bug3777\Lorem<Exception, stdClass>.',
				95,
			],
			[
				'Property Bug3777\Ipsum2::$lorem2 (Bug3777\Lorem2<stdClass, Exception>) does not accept Bug3777\Lorem2<stdClass, object>.',
				129,
			],
			[
				'Property Bug3777\Ipsum2::$ipsum2 (Bug3777\Lorem2<stdClass, Exception>) does not accept Bug3777\Lorem2<Exception, object>.',
				131,
			],
			[
				'Property Bug3777\Ipsum3::$ipsum3 (Bug3777\Lorem3<stdClass, Exception>) does not accept Bug3777\Lorem3<Exception, stdClass>.',
				168,
			],
		]);
	}

}

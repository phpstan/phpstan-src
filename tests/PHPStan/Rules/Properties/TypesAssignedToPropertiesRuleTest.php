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

}

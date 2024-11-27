<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<TypesAssignedToPropertiesRule>
 */
class TypesAssignedToPropertiesRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	protected function getRule(): Rule
	{
		return new TypesAssignedToPropertiesRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, $this->checkExplicitMixed, false, true, false), new PropertyReflectionFinder());
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
			[
				'Property PropertiesAssignedTypes\PostInc::$foo (int<min, 3>) does not accept int<min, 4>.',
				334,
			],
			[
				'Property PropertiesAssignedTypes\PostInc::$bar (int<3, max>) does not accept int<2, max>.',
				335,
			],
			[
				'Property PropertiesAssignedTypes\PostInc::$foo (int<min, 3>) does not accept int<min, 4>.',
				346,
			],
			[
				'Property PropertiesAssignedTypes\PostInc::$bar (int<3, max>) does not accept int<2, max>.',
				347,
			],
			[
				'Property PropertiesAssignedTypes\ListAssign::$foo (string) does not accept int.',
				360,
			],
			[
				'Property PropertiesAssignedTypes\AppendToArrayAccess::$collection2 (ArrayAccess<int, string>&Countable) does not accept Countable.',
				376,
			],
			[
				'Property PropertiesAssignedTypes\ParamOutAssign::$foo (list<string>) does not accept string.',
				400,
				'string is not a list.',
			],
			[
				'Property PropertiesAssignedTypes\ParamOutAssign::$foo2 (list<list<string>>) does not accept string.',
				410,
				'string is not a list.',
			],
			[
				'Property PropertiesAssignedTypes\ParamOutAssign::$foo2 (list<list<string>>) does not accept non-empty-list<list<string>|string>.',
				415,
				'list<string>|string might not be a list.',
			],
		]);
	}

	public function testBug1216(): void
	{
		$this->analyse([__DIR__ . '/data/bug-1216.php'], [
			[
				'Property Bug1216PropertyTest\Baz::$untypedBar (string) does not accept int.',
				38,
			],
			[
				'Property Bug1216PropertyTest\Dummy::$foo (Exception) does not accept stdClass.',
				62,
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

		$this->analyse([__DIR__ . '/data/bug-3777-static.php'], [
			[
				'Static property Bug3777Static\Bar::$foo (Bug3777Static\Foo<stdClass>) does not accept Bug3777Static\Fooo<object>.',
				58,
			],
			[
				'Static property Bug3777Static\Ipsum::$ipsum (Bug3777Static\Lorem<stdClass, Exception>) does not accept Bug3777Static\Lorem<Exception, stdClass>.',
				95,
			],
			[
				'Static property Bug3777Static\Ipsum2::$lorem2 (Bug3777Static\Lorem2<stdClass, Exception>) does not accept Bug3777Static\Lorem2<stdClass, object>.',
				129,
			],
			[
				'Static property Bug3777Static\Ipsum2::$ipsum2 (Bug3777Static\Lorem2<stdClass, Exception>) does not accept Bug3777Static\Lorem2<Exception, object>.',
				131,
			],
			[
				'Static property Bug3777Static\Ipsum3::$ipsum3 (Bug3777Static\Lorem3<stdClass, Exception>) does not accept Bug3777Static\Lorem3<Exception, stdClass>.',
				168,
			],
		]);
	}

	public function testAppendendArrayKey(): void
	{
		$this->analyse([__DIR__ . '/../Arrays/data/appended-array-key.php'], [
			[
				'Property AppendedArrayKey\Foo::$intArray (array<int, mixed>) does not accept array<int|string, mixed>.',
				28,
			],
			[
				'Property AppendedArrayKey\Foo::$intArray (array<int, mixed>) does not accept array<int|string, mixed>.',
				30,
			],
			[
				'Property AppendedArrayKey\Foo::$stringArray (array<string, mixed>) does not accept array<int|string, mixed>.',
				31,
			],
			[
				'Property AppendedArrayKey\Foo::$stringArray (array<string, mixed>) does not accept array<int|string, mixed>.',
				33,
			],
			[
				'Property AppendedArrayKey\Foo::$stringArray (array<string, mixed>) does not accept array<int|string, mixed>.',
				38,
			],
			[
				'Property AppendedArrayKey\Foo::$stringArray (array<string, mixed>) does not accept array<int|string, mixed>.',
				46,
			],
			[
				'Property AppendedArrayKey\MorePreciseKey::$test (array<1|2|3, string>) does not accept non-empty-array<int, string>.',
				80,
			],
			[
				'Property AppendedArrayKey\MorePreciseKey::$test (array<1|2|3, string>) does not accept non-empty-array<1|2|3|4, string>.',
				85,
			],
		]);
	}

	public function testBug5372Two(): void
	{
		$this->analyse([__DIR__ . '/../Arrays/data/bug-5372_2.php'], []);
	}

	public function testBug5447(): void
	{
		$this->analyse([__DIR__ . '/../Arrays/data/bug-5447.php'], []);
	}

	public function testAppendedArrayItemType(): void
	{
		$this->analyse(
			[__DIR__ . '/../Arrays/data/appended-array-item.php'],
			[
				[
					'Property AppendedArrayItem\Foo::$integers (array<int>) does not accept array<int|string>.',
					18,
				],
				[
					'Property AppendedArrayItem\Foo::$callables (array<callable(): mixed>) does not accept non-empty-array<array{1, 2, 3}|(callable(): mixed)>.',
					20,
				],
				[
					'Property AppendedArrayItem\Foo::$callables (array<callable(): mixed>) does not accept non-empty-array<array{\'AppendedArrayItem\\\\Foo\', \'classMethod\'}|(callable(): mixed)>.',
					23,
				],
				[
					'Property AppendedArrayItem\Foo::$callables (array<callable(): mixed>) does not accept non-empty-array<array{\'Foo\', \'Hello world\'}|(callable(): mixed)>.',
					25,
				],
				[
					'Property AppendedArrayItem\Foo::$integers (array<int>) does not accept array<int|string>.',
					27,
				],
				[
					'Property AppendedArrayItem\Foo::$integers (array<int>) does not accept array<int|string>.',
					32,
				],
				[
					'Property AppendedArrayItem\Bar::$stringCallables (array<callable(): string>) does not accept non-empty-array<(callable(): string)|(Closure(): 1)>.',
					45,
				],
				[
					'Property AppendedArrayItem\Baz::$staticProperty (array<AppendedArrayItem\Lorem>) does not accept array<AppendedArrayItem\Baz>.',
					79,
				],
			],
		);
	}

	public function testBug5804(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5804.php'], [
			[
				'Property Bug5804\Blah::$value (array<int>|null) does not accept array<int|string>.',
				12,
			],
			[
				'Property Bug5804\Blah::$value (array<int>|null) does not accept array<Bug5804\Blah|int>.',
				17,
			],
		]);
	}

	public function testBug6286(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->analyse([__DIR__ . '/data/bug-6286.php'], [
			[
				'Property Bug6286\HelloWorld::$details (array{name: string, age: int}) does not accept array{name: string, age: \'Forty-two\'}.',
				19,
				"Offset 'age' (int) does not accept type string.",
			],
			[
				"Property Bug6286\HelloWorld::\$nestedDetails (array<array{name: string, age: int}>) does not accept non-empty-array<array{name: string, age: 'Eleventy-one'|int}>.",
				22,
				"Offset 'age' (int) does not accept type int|string.",
			],
		]);
	}

	public function testBug4906(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4906.php'], []);
	}

	public function testBug4910(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4910.php'], []);
	}

	public function testBug3703(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3703.php'], [
			[
				'Property Bug3703\Foo::$bar (array<string, array<string, array<int>>>) does not accept array<string, array<string, array<int|string>>>.',
				15,
			],
			[
				'Property Bug3703\Foo::$bar (array<string, array<string, array<int>>>) does not accept array<string, array<string, array<int|string>|int>>.',
				18,
			],
			[
				'Property Bug3703\Foo::$bar (array<string, array<string, array<int>>>) does not accept array<string, array<string, array<int>>|string>.',
				21,
			],
		]);
	}

	public function testBug6333(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6333.php'], []);
	}

	public function testBug3339(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3339.php'], []);
	}

	public function testBug5336(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5336.php'], []);
	}

	public function testBug6117(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6117.php'], []);
	}

	public function testGenericObjectWithUnspecifiedTemplateTypes(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/generic-object-unspecified-template-types.php'], [
			[
				'Property GenericObjectUnspecifiedTemplateTypes\Foo::$obj (GenericObjectUnspecifiedTemplateTypes\MyObject<int, string>) does not accept GenericObjectUnspecifiedTemplateTypes\MyObject<(int|string), mixed>.',
				13,
			],
			[
				'Property GenericObjectUnspecifiedTemplateTypes\Bar::$ints (GenericObjectUnspecifiedTemplateTypes\ArrayCollection<int, int>) does not accept GenericObjectUnspecifiedTemplateTypes\ArrayCollection<int, string>.',
				67,
			],
		]);
	}

	public function testGenericObjectWithUnspecifiedTemplateTypesLevel8(): void
	{
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/generic-object-unspecified-template-types.php'], [
			[
				'Property GenericObjectUnspecifiedTemplateTypes\Bar::$ints (GenericObjectUnspecifiedTemplateTypes\ArrayCollection<int, int>) does not accept GenericObjectUnspecifiedTemplateTypes\ArrayCollection<int, string>.',
				67,
			],
		]);
	}

	public function testBug5382(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/bug-5382.php'], []);
	}

	public function testBug6757(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6757.php'], []);
	}

	public function testBug4526(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-4526.php'], []);
	}

	public function testBug7200(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7200.php'], []);
	}

	public function testBug4680(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-4680.php'], []);
	}

	public function testBug3383(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-3383.php'], []);
	}

	public function testBug6356(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6356.php'], []);
	}

	public function testBug6356b(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-6356b.php'], [
			[
				'Property Bug6356b\HelloWorld2::$details (array{name: string, age: int}) does not accept array{name: string, age: \'Forty-two\'}.',
				19,
				"Offset 'age' (int) does not accept type string.",
			],
			[
				'Property Bug6356b\HelloWorld2::$nestedDetails (array<array{name: string, age: int}>) does not accept non-empty-array<array{name: string, age: \'Eleventy-one\'|int}>.',
				21,
				"Offset 'age' (int) does not accept type int|string.",
			],
			[
				'Property Bug6356b\HelloWorld2::$nestedDetails (array<array{name: string, age: int}>) does not accept non-empty-array<array{name: string, age: \'Twelve\'|int}>.',
				26,
				"Offset 'age' (int) does not accept type int|string.",
			],
		]);
	}

	public function testIntegerRangesAndConstants(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/int-ranges-and-constants.php'], [
			[
				'Property IntegerRangesAndConstants\HelloWorld::$i (0|1|3) does not accept int<0, 3>.',
				17,
			],
			[
				'Property IntegerRangesAndConstants\HelloWorld::$x (int<0, 3>) does not accept 0|1|2|3|string.',
				42,
			],
			[
				'Property IntegerRangesAndConstants\HelloWorld::$x (int<0, 3>) does not accept 0|1|2|3|bool.',
				43,
			],
			[
				'Property IntegerRangesAndConstants\HelloWorld::$x (int<0, 3>) does not accept 0|1|3|bool.',
				44,
			],
			[
				'Property IntegerRangesAndConstants\HelloWorld::$x (int<0, 3>) does not accept 0|1|3|4.',
				45,
			],
		]);
	}

	public function testBug3311b(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-3311b.php'], [
			[
				'Property Bug3311b\Foo::$bar (list<string>) does not accept non-empty-array<int<0, max>, string>.',
				16,
				'non-empty-array<int<0, max>, string> might not be a list.',
			],
		]);
	}

	public function testBug7789(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7789.php'], []);
	}

	public function testBug9131(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-9131.php'], []);
	}

	public function testBug8222(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-8222.php'], []);
	}

	public function testWritingReadonlyProperty(): void
	{
		$this->analyse([__DIR__ . '/data/writing-to-read-only-properties.php'], [
			[
				'Property WritingToReadOnlyProperties\Foo::$usualProperty (int) does not accept string.',
				24,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$writeOnlyProperty (int) does not accept string.',
				27,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$usualProperty (int) does not accept string.',
				34,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$writeOnlyProperty (int) does not accept string.',
				40,
			],
		]);
	}

	public function testBug8190(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-8190.php'], []);
	}

	public function testBug8074(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-8074.php'], []);
	}

	public function testBug7087(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-7087.php'], []);
	}

	public function testUnset(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/property-type-after-unset.php'], [
			[
				'Property PropertyTypeAfterUnset\Foo::$nonEmpty (non-empty-array<int>) does not accept array<int>.',
				19,
				'array<int> might be empty.',
			],
			[
				'Property PropertyTypeAfterUnset\Foo::$listProp (list<int>) does not accept array<int<0, max>, int>.',
				20,
				'array<int<0, max>, int> might not be a list.',
			],
			[
				'Property PropertyTypeAfterUnset\Foo::$nestedListProp (array<list<int>>) does not accept array<array<int<0, max>, int>>.',
				21,
				'array<int<0, max>, int> might not be a list.',
			],
		]);
	}

	public function testGenericsInCallableInConstructor(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/generics-in-callable-in-constructor.php'], []);
	}

	public function testBug10686(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10686.php'], []);
	}

	public function testBug11275(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-11275.php'], [
			[
				'Property Bug11275\D::$b (list<Bug11275\B>) does not accept array<int|string, Bug11275\B>.',
				50,
				'array<int|string, Bug11275\B> might not be a list.',
			],
		]);
	}

	public function testBug11617(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-11617.php'], [
			[
				'Property Bug11617\HelloWorld::$params (array<string, string>) does not accept array<int|string, array|string>.',
				14,
			],
			[
				'Property Bug11617\HelloWorld::$params (array<string, string>) does not accept array<int|string, array|string>.',
				16,
			],
			[
				'Property Bug11617\HelloWorld::$params (array<string, string>) does not accept array<int|string, array|string>.',
				21,
			],
		]);
	}

	public function testBug12131(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-12131.php'], [
			[
				'Property Bug12131\Test::$array (non-empty-list<int>) does not accept non-empty-array<int<0, max>, int>.',
				29,
				'non-empty-array<int<0, max>, int> might not be a list.',
			],
		]);
	}

}

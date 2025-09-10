<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<AccessStaticPropertiesRule>
 */
class AccessStaticPropertiesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		return new AccessStaticPropertiesRule(
			$reflectionProvider,
			new RuleLevelHelper($reflectionProvider, true, false, true, false, false, false, true),
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck(self::getContainer()),
				$reflectionProvider,
				self::getContainer(),
			),
			true,
		);
	}

	public function testAccessStaticProperties(): void
	{
		$this->analyse([__DIR__ . '/data/access-static-properties.php'], [
			[
				'Access to an undefined static property FooAccessStaticProperties::$bar.',
				23,
			],
			[
				'Access to an undefined static property BarAccessStaticProperties::$bar.',
				24,
			],
			[
				'Access to an undefined static property FooAccessStaticProperties::$bar.',
				25,
			],
			[
				'Static access to instance property FooAccessStaticProperties::$loremIpsum.',
				26,
			],
			[
				'Static access to instance property FooAccessStaticProperties::$loremIpsum.',
				32,
			],
			[
				'IpsumAccessStaticProperties::ipsum() accesses parent::$lorem but IpsumAccessStaticProperties does not extend any class.',
				42,
			],
			[
				'Access to protected property $foo of class FooAccessStaticProperties.',
				44,
			],
			[
				'Access to static property $test on an unknown class UnknownStaticProperties.',
				47,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$baz.',
				49,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$baz.',
				52,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$baz.',
				53,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$nonexistent.',
				54,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$nonexistent.',
				55,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$emptyBaz.',
				60,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$emptyBaz.',
				63,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$emptyNonexistent.',
				64,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$emptyNonexistent.',
				65,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherNonexistent.',
				70,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherNonexistent.',
				71,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherNonexistent.',
				71,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherNonexistent.',
				72,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherNonexistent.',
				72,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherNonexistent.',
				73,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherEmptyNonexistent.',
				75,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherEmptyNonexistent.',
				75,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherEmptyNonexistent.',
				76,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherEmptyNonexistent.',
				77,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherEmptyNonexistent.',
				78,
			],
			[
				'Access to an undefined static property static(IpsumAccessStaticProperties)::$anotherEmptyNonexistent.',
				78,
			],
			[
				'Accessing self::$staticFooProperty outside of class scope.',
				84,
			],
			[
				'Accessing static::$staticFooProperty outside of class scope.',
				85,
			],
			[
				'Accessing parent::$staticFooProperty outside of class scope.',
				86,
			],
			[
				'Access to protected property $foo of class FooAccessStaticProperties.',
				89,
			],
			[
				'Static access to instance property FooAccessStaticProperties::$loremIpsum.',
				90,
			],
			[
				'Access to an undefined static property FooAccessStaticProperties::$nonexistent.',
				94,
			],
			[
				'Access to static property $test on an unknown class NonexistentClass.',
				97,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Access to an undefined static property FooAccessStaticProperties&SomeInterface::$nonexistent.',
				108,
			],
			[
				'Cannot access static property $foo on int|string.',
				113,
			],
			[
				'Class FooAccessStaticProperties referenced with incorrect case: FOOAccessStaticPropertieS.',
				119,
			],
			[
				'Access to an undefined static property FooAccessStaticProperties::$unknownProperties.',
				119,
			],
			[
				'Class FooAccessStaticProperties referenced with incorrect case: FOOAccessStaticPropertieS.',
				120,
			],
			[
				'Static access to instance property FooAccessStaticProperties::$loremIpsum.',
				120,
			],
			[
				'Class FooAccessStaticProperties referenced with incorrect case: FOOAccessStaticPropertieS.',
				121,
			],
			[
				'Access to protected property $foo of class FooAccessStaticProperties.',
				121,
			],
			[
				'Class FooAccessStaticProperties referenced with incorrect case: FOOAccessStaticPropertieS.',
				122,
			],
			[
				'Access to an undefined static property ClassOrString|string::$unknownProperty.',
				141,
			],
			[
				'Cannot access static property $anotherProperty on ClassOrString|false.',
				150,
			],
			[
				'Static access to instance property ClassOrString::$instanceProperty.',
				152,
			],
			[
				'Access to an undefined static property AccessInIsset::$foo.',
				178,
			],
			[
				'Access to an undefined static property AccessInIsset::$foo.',
				185,
			],
			[
				'Access to static property $foo on an unknown class TraitWithStaticProperty.',
				209,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Static access to instance property AccessWithStatic::$bar.',
				223,
			],
			[
				'Access to an undefined static property static(AccessWithStatic)::$nonexistent.',
				224,
			],
			[
				'Access to an undefined static property DoesNotAllowDynamicProperties::$foo.',
				234,
			],
			[
				'Access to an undefined static property AllowsDynamicProperties::$foo.',
				248,
			],
			[
				'Static access to instance property ParentClassWithInstanceProperty::$i.',
				267,
			],
			[
				'Access to an undefined static property ParentClassWithInstanceProperty::$j.',
				268,
			],
		]);
	}

	public function testRuleAssignOp(): void
	{
		$this->analyse([__DIR__ . '/data/access-static-properties-assign-op.php'], [
			[
				'Access to an undefined static property AccessStaticProperties\AssignOpNonexistentProperty::$flags.',
				10,
			],
		]);
	}

	public function testClassExists(): void
	{
		$this->analyse([__DIR__ . '/data/static-properties-class-exists.php'], []);
	}

	public function testBug5143(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5143.php'], []);
	}

	public function testBug6809(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6809.php'], [
			[
				'Access to an undefined static property static(Bug6809\HelloWorld)::$coolClass.',
				7,
			],
		]);
	}

	public function testBug8333(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8333.php'], [
			[
				'Access to an undefined static property static(Bug8333\BarAccessProperties)::$loremipsum.',
				68,
			],
			[
				'Access to private static property $foo of parent class Bug8333\FooAccessProperties.',
				69,
			],
		]);
	}

	public function testBug12775(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12775.php'], []);
	}

	public function testBug8668Bis(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8668-bis.php'], [
			[
				'Static access to instance property Bug8668Bis\Sample::$sample.',
				9,
			],
			[
				'Static access to instance property Bug8668Bis\Sample::$sample.',
				10,
			],
			[
				'Static access to instance property Bug8668Bis\Sample2::$sample.',
				20,
			],
			[
				'Static access to instance property Bug8668Bis\Sample2::$sample.',
				21,
			],
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<EnumSanityRule>
 */
class EnumSanityRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new EnumSanityRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$expected = [
			[
				'Enum EnumSanity\EnumWithAbstractMethod contains abstract method foo().',
				7,
			],
			[
				'Enum EnumSanity\EnumWithConstructorAndDestructor contains constructor.',
				12,
			],
			[
				'Enum EnumSanity\EnumWithConstructorAndDestructor contains destructor.',
				15,
			],
			[
				'Enum EnumSanity\EnumWithMagicMethods contains magic method __get().',
				21,
			],
			[
				'Enum EnumSanity\EnumWithMagicMethods contains magic method __set().',
				30,
			],
			[
				'Enum EnumSanity\PureEnumCannotRedeclareMethods cannot redeclare native method cases().',
				39,
			],
			[
				'Enum EnumSanity\BackedEnumCannotRedeclareMethods cannot redeclare native method cases().',
				54,
			],
			[
				'Enum EnumSanity\BackedEnumCannotRedeclareMethods cannot redeclare native method tryFrom().',
				58,
			],
			[
				'Enum EnumSanity\BackedEnumCannotRedeclareMethods cannot redeclare native method from().',
				62,
			],
			[
				'Backed enum EnumSanity\BackedEnumWithFloatType can have only "int" or "string" type.',
				67,
			],
			[
				'Backed enum EnumSanity\BackedEnumWithBoolType can have only "int" or "string" type.',
				71,
			],
			[
				'Enum EnumSanity\EnumWithSerialize contains magic method __serialize().',
				78,
			],
			[
				'Enum EnumSanity\EnumWithSerialize contains magic method __unserialize().',
				81,
			],
			[
				'Enum EnumSanity\EnumDuplicateValue has duplicate value 1 for cases A, E.',
				86,
			],
			[
				'Enum EnumSanity\EnumDuplicateValue has duplicate value 2 for cases B, C.',
				86,
			],
			[
				'Enum case EnumSanity\EnumInconsistentCaseType::FOO value \'foo\' does not match the "int" type.',
				105,
			],
			[
				'Enum case EnumSanity\EnumInconsistentCaseType::BAR does not have a value but the enum is backed with the "int" type.',
				106,
			],
			[
				'Enum case EnumSanity\EnumInconsistentStringCaseType::BAR does not have a value but the enum is backed with the "string" type.',
				110,
			],
			[
				'Enum EnumSanity\EnumWithValueButNotBacked is not backed, but case FOO has value 1.',
				114,
			],
		];

		if (PHP_VERSION_ID >= 80100) {
			$expected[] = [
				'Enum EnumSanity\EnumMayNotSerializable cannot implement the Serializable interface.',
				117,
			];
		}

		$this->analyse([__DIR__ . '/data/enum-sanity.php'], $expected);
	}

	public function testBug9402(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->analyse([__DIR__ . '/data/bug-9402.php'], [
			[
				'Enum case Bug9402\Foo::Two value \'foo\' does not match the "int" type.',
				13,
			],
		]);
	}

}

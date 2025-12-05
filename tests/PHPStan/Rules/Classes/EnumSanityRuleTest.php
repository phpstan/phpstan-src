<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<EnumSanityRule>
 */
class EnumSanityRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new EnumSanityRule(
			self::getContainer()->getByType(InitializerExprTypeResolver::class),
		);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$expected = [
			/*[
				// reported by AbstractMethodInNonAbstractClassRule
				'Enum EnumSanity\EnumWithAbstractMethod contains abstract method foo().',
				7,
			],*/
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
			[
				'Enum EnumSanity\EnumMayNotSerializable cannot implement the Serializable interface.',
				117,
			],
		];

		$this->analyse([__DIR__ . '/data/enum-sanity.php'], $expected);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug9402(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9402.php'], [
			[
				'Enum case Bug9402\Foo::Two value \'foo\' does not match the "int" type.',
				13,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug11592(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11592.php'], [
			[
				'Enum Bug11592\Test2 cannot redeclare native method cases().',
				22,
			],
			[
				'Enum Bug11592\BackedTest2 cannot redeclare native method cases().',
				37,
			],
			[
				'Enum Bug11592\BackedTest2 cannot redeclare native method from().',
				39,
			],
			[
				'Enum Bug11592\BackedTest2 cannot redeclare native method tryFrom().',
				41,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug13768(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13768.php'], [
			[
				'Enum Bug13768\Order is not backed, but case A has value 1.5.',
				7,
			],
			[
				'Enum Bug13768\Order is not backed, but case B has value 2.5.',
				8,
			],
			[
				'Enum Bug13768\Order is not backed, but case C has value 3.',
				9,
			],
			[
				'Enum Bug13768\Order is not backed, but case D has value \'3\'.',
				10,
			],
			[
				'Enum Bug13768\Order is not backed, but case E has value false.',
				11,
			],
			[
				'Enum Bug13768\Order is not backed, but case F has value 1.',
				12,
			],
			[
				'Enum Bug13768\Backed has duplicate value 1 for cases One, Two.',
				20,
			],
		]);
	}

}

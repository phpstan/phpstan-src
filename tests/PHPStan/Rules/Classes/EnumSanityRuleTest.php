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
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection');
		}

		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->analyse([__DIR__ . '/data/enum-sanity.php'], [
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
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ExistingClassesInEnumImplementsRule>
 */
class ExistingClassesInEnumImplementsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new ExistingClassesInEnumImplementsRule(
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck($container),
				$reflectionProvider,
				$container,
			),
			$reflectionProvider,
			true,
		);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/enum-implements.php'], [
			[
				'Interface EnumImplements\FooInterface referenced with incorrect case: EnumImplements\FOOInterface.',
				30,
			],
			[
				'Enum EnumImplements\Foo3 implements class EnumImplements\FooClass.',
				35,
			],
			[
				'Enum EnumImplements\Foo4 implements trait EnumImplements\FooTrait.',
				40,
			],
			[
				'Enum EnumImplements\Foo5 implements enum EnumImplements\FooEnum.',
				45,
			],
			[
				'Enum EnumImplements\Foo6 implements unknown interface EnumImplements\NonexistentInterface.',
				50,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Enum EnumImplements\FooEnum referenced with incorrect case: EnumImplements\FOOEnum.',
				55,
			],
			[
				'Enum EnumImplements\Foo7 implements enum EnumImplements\FooEnum.',
				55,
			],
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ExistingClassesInInterfaceExtendsRule>
 */
class ExistingClassesInInterfaceExtendsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ExistingClassesInInterfaceExtendsRule(
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

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/extends-implements.php'], [
			[
				'Interface ExtendsImplements\FooInterface referenced with incorrect case: ExtendsImplements\FOOInterface.',
				30,
			],
		]);
	}

	public function testRuleExtendsError(): void
	{
		$this->analyse([__DIR__ . '/data/interface-extends-error.php'], [
			[
				'Interface InterfaceExtendsError\Foo extends unknown interface InterfaceExtendsError\Bar.',
				5,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Interface InterfaceExtendsError\Lorem extends class InterfaceExtendsError\BazClass.',
				15,
			],
			[
				'Interface InterfaceExtendsError\Ipsum extends trait InterfaceExtendsError\DolorTrait.',
				25,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testEnums(): void
	{
		$this->analyse([__DIR__ . '/data/interface-extends-enum.php'], [
			[
				'Interface InterfaceExtendsEnum\Foo extends enum InterfaceExtendsEnum\FooEnum.',
				10,
			],
		]);
	}

}

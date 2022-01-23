<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ExistingClassesInInterfaceExtendsRule>
 */
class ExistingClassesInInterfaceExtendsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ExistingClassesInInterfaceExtendsRule(
			new ClassCaseSensitivityCheck($broker, true),
			$broker,
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
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('This test needs static reflection');
		}

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

	public function testEnums(): void
	{
		if (!self::$useStaticReflectionProvider || PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs static reflection and PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/interface-extends-enum.php'], [
			[
				'Interface InterfaceExtendsEnum\Foo extends enum InterfaceExtendsEnum\FooEnum.',
				10,
			],
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ExistingClassesInClassImplementsRule>
 */
class ExistingClassesInClassImplementsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new ExistingClassesInClassImplementsRule(
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck(self::getContainer()),
			),
			$reflectionProvider,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/extends-implements.php'], [
			[
				'Interface ExtendsImplements\FooInterface referenced with incorrect case: ExtendsImplements\FOOInterface.',
				15,
			],
		]);
	}

	public function testRuleImplementsError(): void
	{
		$this->analyse([__DIR__ . '/data/implements-error.php'], [
			[
				'Class ImplementsError\Foo implements unknown interface ImplementsError\Bar.',
				5,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Class ImplementsError\Lorem implements class ImplementsError\Foo.',
				10,
			],
			[
				'Class ImplementsError\Ipsum implements trait ImplementsError\DolorTrait.',
				20,
			],
			[
				'Anonymous class implements trait ImplementsError\DolorTrait.',
				25,
			],
		]);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/class-implements-enum.php'], [
			[
				'Class ClassImplementsEnum\Foo implements enum ClassImplementsEnum\FooEnum.',
				10,
			],
			[
				'Anonymous class implements enum ClassImplementsEnum\FooEnum.',
				16,
			],
		]);
	}

	public function testBug8889(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8889.php'], [
			[
				'Class Bug8889\HelloWorld implements unknown interface iterable.',
				5,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Class Bug8889\HelloWorld2 implements unknown interface Iterable.',
				8,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

}

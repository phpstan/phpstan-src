<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ExistingClassInInstanceOfRule>
 */
class ExistingClassInInstanceOfRuleTest extends RuleTestCase
{

	private bool $shouldNarrowMethodScopeFromConstructor = true;

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new ExistingClassInInstanceOfRule(
			$reflectionProvider,
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck(self::getContainer()),
			),
			true,
			true,
		);
	}

	public function shouldNarrowMethodScopeFromConstructor(): bool
	{
		return $this->shouldNarrowMethodScopeFromConstructor;
	}

	public function testClassDoesNotExist(): void
	{
		$this->analyse(
			[
				__DIR__ . '/data/instanceof.php',
				__DIR__ . '/data/instanceof-defined.php',
			],
			[
				[
					'Class InstanceOfNamespaceRule\Bar not found.',
					7,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Using self outside of class scope.',
					9,
				],
				[
					'Class InstanceOfNamespaceRule\Foo referenced with incorrect case: InstanceOfNamespaceRule\FOO.',
					13,
				],
				[
					'Using parent outside of class scope.',
					15,
				],
				[
					'Using self outside of class scope.',
					17,
				],
			],
		);
	}

	public function testClassExists(): void
	{
		$this->analyse([__DIR__ . '/data/instanceof-class-exists.php'], []);
	}

	public function testBug7720(): void
	{
		if (PHP_VERSION_ID < 80000) {
			self::markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-7720.php'], [
			[
				'Instanceof between mixed and trait Bug7720\FooBar will always evaluate to false.',
				17,
			],
		]);
	}

	public function testRememberClassExistsFromConstructorDisabled(): void
	{
		$this->shouldNarrowMethodScopeFromConstructor = false;

		$this->analyse([__DIR__ . '/data/remember-class-exists-from-constructor.php'], [
			[
				'Class SomeUnknownClass not found.',
				19,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Class SomeUnknownInterface not found.',
				38,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testRememberClassExistsFromConstructor(): void
	{
		$this->analyse([__DIR__ . '/data/remember-class-exists-from-constructor.php'], []);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ExistingClassInInstanceOfRule>
 */
class ExistingClassInInstanceOfRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ExistingClassInInstanceOfRule(
			$broker,
			new ClassCaseSensitivityCheck($broker, true),
			true,
		);
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
					'Class InstanceOfNamespace\Bar not found.',
					7,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Using self outside of class scope.',
					9,
				],
				[
					'Class InstanceOfNamespace\Foo referenced with incorrect case: InstanceOfNamespace\FOO.',
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
		$this->analyse([__DIR__ . '/data/bug-7720.php'], [
			[
				'Instanceof between mixed and trait Bug7720\FooBar will always evaluate to false.',
				17,
			],
		]);
	}

	public function testTraitInstanceOf(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/trait-instance-of.php'], [
			[
				'Instanceof between $this(TraitInstanceOf\ATrait1Class) and trait TraitInstanceOf\Trait2 will always evaluate to false.',
				21,
			],
		]);
	}

}

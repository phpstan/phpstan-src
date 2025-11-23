<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<PropertyTagRule>
 */
class PropertyTagRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new PropertyTagRule(
			new PropertyTagCheck(
				$reflectionProvider,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
				),
				new GenericObjectTypeCheck(),
				new MissingTypehintCheck(true, []),
				new UnresolvableTypeHelper(),
				true,
				true,
				true,
			),
		);
	}

	public function testRule(): void
	{
		$tipText = 'Learn more at https://phpstan.org/user-guide/discovering-symbols';
		$fooClassLine = 23;

		$this->analyse([__DIR__ . '/data/property-tag.php'], [
			[
				'PHPDoc tag @property for property PropertyTag\Foo::$a contains unknown class PropertyTag\intt.',
				$fooClassLine,
				$tipText,
			],
			[
				'PHPDoc tag @property for property PropertyTag\Foo::$b contains unknown class PropertyTag\intt.',
				$fooClassLine,
				$tipText,
			],
			[
				'PHPDoc tag @property for property PropertyTag\Foo::$c contains unknown class PropertyTag\stringg.',
				$fooClassLine,
				$tipText,
			],
			[
				'PHPDoc tag @property for property PropertyTag\Foo::$c contains unknown class PropertyTag\intt.',
				$fooClassLine,
				$tipText,
			],
			[
				'PHPDoc tag @property for property PropertyTag\Foo::$d contains unknown class PropertyTag\intt.',
				$fooClassLine,
				$tipText,
			],
			[
				'PHPDoc tag @property for property PropertyTag\Foo::$e contains unknown class PropertyTag\stringg.',
				$fooClassLine,
				$tipText,
			],
			[
				'PHPDoc tag @property for property PropertyTag\Foo::$e contains unknown class PropertyTag\intt.',
				$fooClassLine,
				$tipText,
			],
			[
				'PHPDoc tag @property-read for property PropertyTag\Foo::$f contains unknown class PropertyTag\intt.',
				$fooClassLine,
				$tipText,
			],
			[
				'PHPDoc tag @property-write for property PropertyTag\Foo::$g contains unknown class PropertyTag\stringg.',
				$fooClassLine,
				$tipText,
			],
			[
				'PHPDoc tag @property for property PropertyTag\Bar::$unresolvable contains unresolvable type.',
				31,
			],
			[
				'PHPDoc tag @property for property PropertyTag\TestGenerics::$a contains generic type Exception<int> but class Exception is not generic.',
				51,
			],
			[
				'Generic type PropertyTag\Generic<int> in PHPDoc tag @property for property PropertyTag\TestGenerics::$b does not specify all template types of class PropertyTag\Generic: T, U',
				51,
			],
			[
				'Generic type PropertyTag\Generic<int, string, float> in PHPDoc tag @property for property PropertyTag\TestGenerics::$c specifies 3 template types, but class PropertyTag\Generic supports only 2: T, U',
				51,
			],
			[
				'Type string in generic type PropertyTag\Generic<string, string> in PHPDoc tag @property for property PropertyTag\TestGenerics::$d is not subtype of template type T of int of class PropertyTag\Generic.',
				51,
			],
			[
				'PHPDoc tag @property for property PropertyTag\MissingGenerics::$a contains generic class PropertyTag\Generic but does not specify its types: T, U',
				59,
			],
			[
				'Class PropertyTag\MissingIterableValue has PHPDoc tag @property for property $a with no value type specified in iterable type array.',
				67,
				'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
			],
			[
				'Class PropertyTag\MissingCallableSignature has PHPDoc tag @property for property $a with no signature specified for callable.',
				75,
			],
			[
				'PHPDoc tag @property for property PropertyTag\NonexistentClasses::$a contains unknown class PropertyTag\Nonexistent.',
				85,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @property for property PropertyTag\NonexistentClasses::$b contains invalid type PropertyTagTrait\Foo.',
				85,
			],
			[
				'Class PropertyTag\Foo referenced with incorrect case: PropertyTag\fOO.',
				85,
			],
		]);
	}

}

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
 * @extends RuleTestCase<MethodTagRule>
 */
class MethodTagRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new MethodTagRule(
			new MethodTagCheck(
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
		$fooClassLine = 12;
		$this->analyse([__DIR__ . '/data/method-tag.php'], [
			[
				'PHPDoc tag @method for method MethodTag\Foo::doFoo() return type contains unknown class MethodTag\intt.',
				$fooClassLine,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @method for method MethodTag\Foo::doBar() parameter #1 $a contains unresolvable type.',
				$fooClassLine,
			],
			[
				'PHPDoc tag @method for method MethodTag\Foo::doBaz2() parameter #1 $a default value contains unresolvable type.',
				12,
			],
			[
				'Class MethodTag\Foo has PHPDoc tag @method for method doMissingIterablueValue() return type with no value type specified in iterable type array.',
				12,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'PHPDoc tag @method for method MethodTag\TestGenerics::doA() return type contains generic type Exception<int> but class Exception is not generic.',
				39,
			],
			[
				'Generic type MethodTag\Generic<int> in PHPDoc tag @method for method MethodTag\TestGenerics::doB() return type does not specify all template types of class MethodTag\Generic: T, U',
				39,
			],
			[
				'Generic type MethodTag\Generic<int, string, float> in PHPDoc tag @method for method MethodTag\TestGenerics::doC() return type specifies 3 template types, but class MethodTag\Generic supports only 2: T, U',
				39,
			],
			[
				'Type string in generic type MethodTag\Generic<string, string> in PHPDoc tag @method for method MethodTag\TestGenerics::doD() return type is not subtype of template type T of int of class MethodTag\Generic.',
				39,
			],
			[
				'PHPDoc tag @method for method MethodTag\MissingGenerics::doA() return type contains generic class MethodTag\Generic but does not specify its types: T, U',
				47,
			],
			[
				'Class MethodTag\MissingIterableValue has PHPDoc tag @method for method doA() return type with no value type specified in iterable type array.',
				55,
				'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
			],
			[
				'Class MethodTag\MissingCallableSignature has PHPDoc tag @method for method doA() return type with no signature specified for callable.',
				63,
			],
			[
				'PHPDoc tag @method for method MethodTag\NonexistentClasses::doA() return type contains unknown class MethodTag\Nonexistent.',
				73,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @method for method MethodTag\NonexistentClasses::doB() return type contains invalid type PropertyTagTrait\Foo.',
				73,
			],
			[
				'Class MethodTag\Foo referenced with incorrect case: MethodTag\fOO.',
				73,
			],
		]);
	}

}

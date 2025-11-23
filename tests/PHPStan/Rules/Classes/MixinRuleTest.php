<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<MixinRule>
 */
class MixinRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new MixinRule(
			new MixinCheck(
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
		$this->analyse([__DIR__ . '/data/mixin.php'], [
			[
				'PHPDoc tag @mixin contains non-object type int.',
				16,
			],
			[
				'PHPDoc tag @mixin contains unresolvable type.',
				24,
			],
			[
				'PHPDoc tag @mixin contains generic type Exception<MixinRule\Foo> but class Exception is not generic.',
				34,
			],
			[
				'Generic type Traversable<int, int, int> in PHPDoc tag @mixin specifies 3 template types, but interface Traversable supports only 2: TKey, TValue',
				34,
			],
			[
				'Type string in generic type ReflectionClass<string> in PHPDoc tag @mixin is not subtype of template type T of object of class ReflectionClass.',
				34,
			],
			[
				'PHPDoc tag @mixin contains generic class ReflectionClass but does not specify its types: T',
				50,
			],
			[
				'PHPDoc tag @mixin contains unknown class MixinRule\UnknownestClass.',
				50,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @mixin contains invalid type MixinRule\FooTrait.',
				50,
			],
			[
				'PHPDoc tag @mixin contains unknown class MixinRule\U.',
				59,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Generic type MixinRule\Consecteur<MixinRule\Foo> in PHPDoc tag @mixin does not specify all template types of class MixinRule\Consecteur: T, U',
				76,
			],
			[
				'Class MixinRule\Foo referenced with incorrect case: MixinRule\foo.',
				84,
			],
			[
				'PHPDoc tag @mixin contains non-object type int.',
				92,
			],
			[
				'Call-site variance of contravariant MixinRule\Foo in generic type MixinRule\Adipiscing<contravariant MixinRule\Foo> in PHPDoc tag @mixin is in conflict with covariant template type T of class MixinRule\Adipiscing.',
				108,
			],
			[
				'Call-site variance of covariant MixinRule\Foo in generic type MixinRule\Adipiscing<covariant MixinRule\Foo> in PHPDoc tag @mixin is redundant, template type T of class MixinRule\Adipiscing has the same variance.',
				116,
				'You can safely remove the call-site variance annotation.',
			],
			[
				'Class MixinRule\NoIterableValue has PHPDoc tag @mixin with no value type specified in iterable type array.',
				124,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'Class MixinRule\NoCallableSignature has PHPDoc tag @mixin with no signature specified for callable.',
				132,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testEnums(): void
	{
		$this->analyse([__DIR__ . '/data/mixin-enums.php'], [
			[
				'PHPDoc tag @mixin contains non-object type int.',
				16,
			],
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MixinRule>
 */
class MixinRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();

		return new MixinRule(
			$reflectionProvider,
			new ClassCaseSensitivityCheck($reflectionProvider, true),
			new GenericObjectTypeCheck(),
			new MissingTypehintCheck($reflectionProvider, true, true, true, []),
			new UnresolvableTypeHelper(),
			true,
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
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
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
		]);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/mixin-enums.php'], [
			[
				'PHPDoc tag @mixin contains non-object type int.',
				16,
			],
		]);
	}

}

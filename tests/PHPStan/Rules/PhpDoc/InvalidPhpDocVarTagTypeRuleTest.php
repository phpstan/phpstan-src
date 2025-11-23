<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<InvalidPhpDocVarTagTypeRule>
 */
class InvalidPhpDocVarTagTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new InvalidPhpDocVarTagTypeRule(
			$container->getByType(FileTypeMapper::class),
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
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-var-tag-type.php'], [
			[
				'PHPDoc tag @var for variable $test contains unresolvable type.',
				13,
			],
			[
				'PHPDoc tag @var contains unresolvable type.',
				16,
			],
			[
				'PHPDoc tag @var for variable $test contains unknown class InvalidVarTagType\aray.',
				20,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @var for variable $value contains unresolvable type.',
				22,
			],
			[
				'PHPDoc tag @var for variable $staticVar contains unresolvable type.',
				27,
			],
			[
				'Class InvalidVarTagType\Foo referenced with incorrect case: InvalidVarTagType\foo.',
				31,
			],
			[
				'PHPDoc tag @var for variable $test has invalid type InvalidVarTagType\FooTrait.',
				34,
			],
			[
				'PHPDoc tag @var for variable $test contains generic type InvalidPhpDoc\Foo<stdClass> but class InvalidPhpDoc\Foo is not generic.',
				40,
			],
			[
				'Generic type InvalidPhpDocDefinitions\FooGeneric<int> in PHPDoc tag @var for variable $test does not specify all template types of class InvalidPhpDocDefinitions\FooGeneric: T, U',
				46,
			],
			[
				'Generic type InvalidPhpDocDefinitions\FooGeneric<int, InvalidArgumentException, string> in PHPDoc tag @var for variable $test specifies 3 template types, but class InvalidPhpDocDefinitions\FooGeneric supports only 2: T, U',
				49,
			],
			[
				'Type Throwable in generic type InvalidPhpDocDefinitions\FooGeneric<int, Throwable> in PHPDoc tag @var for variable $test is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				52,
			],
			[
				'Type stdClass in generic type InvalidPhpDocDefinitions\FooGeneric<int, stdClass> in PHPDoc tag @var for variable $test is not subtype of template type U of Exception of class InvalidPhpDocDefinitions\FooGeneric.',
				55,
			],
			[
				'PHPDoc tag @var for variable $test has no value type specified in iterable type array.',
				58,
				MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP,
			],
			[
				'PHPDoc tag @var for variable $test contains generic class InvalidPhpDocDefinitions\FooGeneric but does not specify its types: T, U',
				61,
			],
			[
				'Call-site variance of covariant int in generic type InvalidPhpDocDefinitions\FooCovariantGeneric<covariant int> in PHPDoc tag @var for variable $test is redundant, template type T of class InvalidPhpDocDefinitions\FooCovariantGeneric has the same variance.',
				67,
				'You can safely remove the call-site variance annotation.',
			],
			[
				'Call-site variance of contravariant int in generic type InvalidPhpDocDefinitions\FooCovariantGeneric<contravariant int> in PHPDoc tag @var for variable $test is in conflict with covariant template type T of class InvalidPhpDocDefinitions\FooCovariantGeneric.',
				73,
			],
			[
				'PHPDoc tag @var for variable $test contains generic class InvalidPhpDocDefinitions\FooGenericWithSomeDefaults but does not specify its types: T, U (1-2 required)',
				79,
			],
			[
				'PHPDoc tag @var for variable $foo contains unknown class InvalidVarTagType\Blabla.',
				85,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testBug4486(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4486.php'], [
			[
				'PHPDoc tag @var for variable $one contains unknown class Some\Namespaced\ClassName1.',
				10,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @var for variable $two contains unknown class Some\Namespaced\ClassName2.',
				10,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @var for variable $three contains unknown class Some\Namespaced\ClassName1.',
				15,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testBug4486Namespace(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4486-ns.php'], [
			[
				'PHPDoc tag @var for variable $one contains unknown class Bug4486Namespace\ClassName1.',
				6,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @var for variable $two contains unknown class Bug4486Namespace\ClassName1.',
				10,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug6252(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6252.php'], []);
	}

	public function testBug6348(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6348.php'], []);
	}

	public function testBug9055(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9055.php'], [
			[
				'PHPDoc tag @var for variable $x contains unknown class Bug9055\uncheckedNotExisting.',
				16,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

}

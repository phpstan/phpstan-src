<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ClassTemplateTypeRule>
 */
class ClassTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $reflectionProvider);

		$container = self::getContainer();
		return new ClassTemplateTypeRule(
			new TemplateTypeCheck(
				$reflectionProvider,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
				),
				new GenericObjectTypeCheck(),
				$typeAliasResolver,
				true,
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/class-template.php'], [
			[
				'PHPDoc tag @template for class ClassTemplateType\Foo cannot have existing class stdClass as its name.',
				8,
			],
			[
				'PHPDoc tag @template T for class ClassTemplateType\Bar has invalid bound type ClassTemplateType\Zazzzu.',
				16,
			],
			[
				'Class ClassTemplateType\Baz referenced with incorrect case: ClassTemplateType\baz.',
				32,
			],
			[
				'PHPDoc tag @template for class ClassTemplateType\Ipsum cannot have existing type alias TypeAlias as its name.',
				41,
			],
			[
				'PHPDoc tag @template for class ClassTemplateType\Dolor cannot have existing type alias LocalAlias as its name.',
				53,
			],
			[
				'PHPDoc tag @template for class ClassTemplateType\Dolor cannot have existing type alias ImportedAlias as its name.',
				53,
			],
			[
				'PHPDoc tag @template for anonymous class cannot have existing class stdClass as its name.',
				58,
			],
			[
				'PHPDoc tag @template T for anonymous class has invalid bound type ClassTemplateType\Zazzzu.',
				63,
			],
			[
				'Class ClassTemplateType\Baz referenced with incorrect case: ClassTemplateType\baz.',
				73,
			],
			[
				'PHPDoc tag @template for anonymous class cannot have existing type alias TypeAlias as its name.',
				78,
			],
			[
				'Call-site variance of covariant int in generic type ClassTemplateType\Consecteur<covariant int> in PHPDoc tag @template U is redundant, template type T of class ClassTemplateType\Consecteur has the same variance.',
				113,
				'You can safely remove the call-site variance annotation.',
			],
			[
				'Call-site variance of contravariant int in generic type ClassTemplateType\Consecteur<contravariant int> in PHPDoc tag @template W is in conflict with covariant template type T of class ClassTemplateType\Consecteur.',
				113,
			],
			[
				'PHPDoc tag @template T for class ClassTemplateType\Elit has invalid default type ClassTemplateType\Zazzzu.',
				121,
			],
			[
				'Default type bool in PHPDoc tag @template T for class ClassTemplateType\Venenatis is not subtype of bound type object.',
				129,
			],
			[
				'PHPDoc tag @template V for class ClassTemplateType\Mauris does not have a default type but follows an optional @template U.',
				139,
			],
		]);
	}

	public function testNestedGenericTypes(): void
	{
		$this->analyse([__DIR__ . '/data/nested-generic-types.php'], [
			[
				'Type mixed in generic type NestedGenericTypesClassCheck\SomeObjectInterface<mixed> in PHPDoc tag @template U is not subtype of template type T of object of interface NestedGenericTypesClassCheck\SomeObjectInterface.',
				32,
			],
			[
				'Type int in generic type NestedGenericTypesClassCheck\SomeObjectInterface<int> in PHPDoc tag @template U is not subtype of template type T of object of interface NestedGenericTypesClassCheck\SomeObjectInterface.',
				41,
			],
			[
				'PHPDoc tag @template U bound contains generic type NestedGenericTypesClassCheck\NotGeneric<mixed> but interface NestedGenericTypesClassCheck\NotGeneric is not generic.',
				52,
			],
			[
				'PHPDoc tag @template V bound has type NestedGenericTypesClassCheck\MultipleGenerics<stdClass> which does not specify all template types of interface NestedGenericTypesClassCheck\MultipleGenerics: T, U',
				52,
			],
			[
				'PHPDoc tag @template W bound has type NestedGenericTypesClassCheck\MultipleGenerics<stdClass, Exception, SplFileInfo> which specifies 3 template types, but interface NestedGenericTypesClassCheck\MultipleGenerics supports only 2: T, U',
				52,
			],
		]);
	}

	public function testBug5446(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5446.php'], []);
	}

	public function testInInterface(): void
	{
		$this->analyse([__DIR__ . '/data/interface-template.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug10049(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10049.php'], [
			[
				'PHPDoc tag @template for class Bug10049\SimpleEntity cannot have existing class Bug10049\SimpleEntity as its name.',
				8,
			],
		]);
	}

}

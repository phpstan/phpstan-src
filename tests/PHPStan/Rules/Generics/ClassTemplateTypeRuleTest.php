<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ClassTemplateTypeRule>
 */
class ClassTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $broker);

		return new ClassTemplateTypeRule(
			new TemplateTypeCheck(
				$broker,
				new ClassCaseSensitivityCheck($broker, true),
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

}

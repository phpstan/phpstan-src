<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\TypeAliasResolver;


/**
 * @extends \PHPStan\Testing\RuleTestCase<ClassTemplateTypeRule>
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
				new ClassCaseSensitivityCheck($broker),
				new GenericObjectTypeCheck(),
				$typeAliasResolver,
				true
			)
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
				'PHPDoc tag @template T for class ClassTemplateType\Baz with bound type float is not supported.',
				24,
			],
			[
				'Class ClassTemplateType\Baz referenced with incorrect case: ClassTemplateType\baz.',
				32,
			],
			[
				'PHPDoc tag @template for class ClassTemplateType\Ipsum cannot have existing type alias TypeAlias as its name.',
				40,
			],
			[
				'PHPDoc tag @template for anonymous class cannot have existing class stdClass as its name.',
				45,
			],
			[
				'PHPDoc tag @template T for anonymous class has invalid bound type ClassTemplateType\Zazzzu.',
				50,
			],
			[
				'PHPDoc tag @template T for anonymous class with bound type float is not supported.',
				55,
			],
			[
				'Class ClassTemplateType\Baz referenced with incorrect case: ClassTemplateType\baz.',
				60,
			],
			[
				'PHPDoc tag @template for anonymous class cannot have existing type alias TypeAlias as its name.',
				65,
			],
		]);
	}

	public function testNestedGenericTypes(): void
	{
		$this->analyse([__DIR__ . '/data/nested-generic-types.php'], [
			[
				'Type mixed in generic type NestedGenericTypesClassCheck\SomeObjectInterface<mixed> in PHPDoc tag @template U is not subtype of template type T of object of class NestedGenericTypesClassCheck\SomeObjectInterface.',
				32,
			],
			[
				'Type int in generic type NestedGenericTypesClassCheck\SomeObjectInterface<int> in PHPDoc tag @template U is not subtype of template type T of object of class NestedGenericTypesClassCheck\SomeObjectInterface.',
				41,
			],
			[
				'PHPDoc tag @template U bound contains generic type NestedGenericTypesClassCheck\NotGeneric<mixed> but class NestedGenericTypesClassCheck\NotGeneric is not generic.',
				52,
			],
			[
				'PHPDoc tag @template V bound has type NestedGenericTypesClassCheck\MultipleGenerics<stdClass> which does not specify all template types of class NestedGenericTypesClassCheck\MultipleGenerics: T, U',
				52,
			],
			[
				'PHPDoc tag @template W bound has type NestedGenericTypesClassCheck\MultipleGenerics<stdClass, Exception, SplFileInfo> which specifies 3 template types, but class NestedGenericTypesClassCheck\MultipleGenerics supports only 2: T, U',
				52,
			],
		]);
	}

}

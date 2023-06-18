<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<MethodTemplateTypeRule>
 */
class MethodTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $broker);

		return new MethodTemplateTypeRule(
			self::getContainer()->getByType(FileTypeMapper::class),
			new TemplateTypeCheck($broker, new ClassCaseSensitivityCheck($broker, true), new GenericObjectTypeCheck(), $typeAliasResolver, true),
		);
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/method-template.php';

		$this->analyse([__DIR__ . '/data/method-template.php'], [
			[
				'PHPDoc tag @template for method MethodTemplateType\Foo::doFoo() cannot have existing class stdClass as its name.',
				11,
			],
			[
				'PHPDoc tag @template T for method MethodTemplateType\Foo::doBar() has invalid bound type MethodTemplateType\Zazzzu.',
				19,
			],
			[
				'PHPDoc tag @template T for method MethodTemplateType\Bar::doFoo() shadows @template T of Exception for class MethodTemplateType\Bar.',
				37,
			],
			[
				'PHPDoc tag @template for method MethodTemplateType\Lorem::doFoo() cannot have existing type alias TypeAlias as its name.',
				66,
			],
			[
				'PHPDoc tag @template for method MethodTemplateType\Ipsum::doFoo() cannot have existing type alias LocalAlias as its name.',
				85,
			],
			[
				'PHPDoc tag @template for method MethodTemplateType\Ipsum::doFoo() cannot have existing type alias ImportedAlias as its name.',
				85,
			],
			[
				'Type projection covariant int in generic type MethodTemplateType\Dolor<covariant int> in PHPDoc tag @template U is redundant, template type T of class MethodTemplateType\Dolor has the same variance.',
				109,
				'You can safely remove the type projection.',
			],
			[
				'Type projection contravariant int in generic type MethodTemplateType\Dolor<contravariant int> in PHPDoc tag @template W is conflicting with variance of template type T of class MethodTemplateType\Dolor.',
				109,
			],
		]);
	}

}

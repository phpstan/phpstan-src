<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<FunctionTemplateTypeRule>
 */
class FunctionTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $reflectionProvider);

		$container = self::getContainer();
		return new FunctionTemplateTypeRule(
			$container->getByType(FileTypeMapper::class),
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
		$this->analyse([__DIR__ . '/data/function-template.php'], [
			[
				'PHPDoc tag @template for function FunctionTemplateType\foo() cannot have existing class stdClass as its name.',
				8,
			],
			[
				'PHPDoc tag @template T for function FunctionTemplateType\bar() has invalid bound type FunctionTemplateType\Zazzzu.',
				16,
			],
			[
				'PHPDoc tag @template for function FunctionTemplateType\lorem() cannot have existing type alias TypeAlias as its name.',
				32,
			],
			[
				'PHPDoc tag @template T for function FunctionTemplateType\resourceBound() with bound type resource is not supported.',
				50,
			],
			[
				'Call-site variance of covariant int in generic type FunctionTemplateType\GenericCovariant<covariant int> in PHPDoc tag @template U is redundant, template type T of class FunctionTemplateType\GenericCovariant has the same variance.',
				94,
				'You can safely remove the call-site variance annotation.',
			],
			[
				'Call-site variance of contravariant int in generic type FunctionTemplateType\GenericCovariant<contravariant int> in PHPDoc tag @template W is in conflict with covariant template type T of class FunctionTemplateType\GenericCovariant.',
				94,
			],
			[
				'PHPDoc tag @template T for function FunctionTemplateType\invalidDefault() has invalid default type FunctionTemplateType\Zazzzu.',
				102,
			],
			[
				'Default type bool in PHPDoc tag @template T for function FunctionTemplateType\outOfBoundsDefault() is not subtype of bound type object.',
				110,
			],
			[
				'PHPDoc tag @template V for function FunctionTemplateType\requiredAfterOptional() does not have a default type but follows an optional @template U.',
				120,
			],
		]);
	}

	public function testBug3769(): void
	{
		require_once __DIR__ . '/data/bug-3769.php';
		$this->analyse([__DIR__ . '/data/bug-3769.php'], []);
	}

}

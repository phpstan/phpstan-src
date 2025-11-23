<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<MethodTagTemplateTypeTraitRule>
 */
class MethodTagTemplateTypeTraitRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $reflectionProvider);

		$container = self::getContainer();
		return new MethodTagTemplateTypeTraitRule(
			new MethodTagTemplateTypeCheck(
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
			),
			$reflectionProvider,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/method-tag-trait-template.php'], [
			[
				'PHPDoc tag @method template U for method MethodTagTraitTemplate\HelloWorld::sayHello() has invalid bound type MethodTagTraitTemplate\Nonexisting.',
				11,
			],
			[
				'PHPDoc tag @method template for method MethodTagTraitTemplate\HelloWorld::sayHello() cannot have existing class stdClass as its name.',
				11,
			],
			[
				'PHPDoc tag @method template T for method MethodTagTraitTemplate\HelloWorld::sayHello() shadows @template T for class MethodTagTraitTemplate\HelloWorld.',
				11,
			],
			[
				'PHPDoc tag @method template for method MethodTagTraitTemplate\HelloWorld::typeAlias() cannot have existing type alias TypeAlias as its name.',
				11,
			],
		]);
	}

}

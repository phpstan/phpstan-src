<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<MethodTagTemplateTypeRule>
 */
class MethodTagTemplateTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$typeAliasResolver = $this->createTypeAliasResolver(['TypeAlias' => 'int'], $reflectionProvider);

		$container = self::getContainer();
		return new MethodTagTemplateTypeRule(
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
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/method-tag-template.php'], [
			[
				'PHPDoc tag @method template U for method MethodTagTemplate\HelloWorld::sayHello() has invalid bound type MethodTagTemplate\Nonexisting.',
				13,
			],
			[
				'PHPDoc tag @method template for method MethodTagTemplate\HelloWorld::sayHello() cannot have existing class stdClass as its name.',
				13,
			],
			[
				'PHPDoc tag @method template T for method MethodTagTemplate\HelloWorld::sayHello() shadows @template T for class MethodTagTemplate\HelloWorld.',
				13,
			],
			[
				'PHPDoc tag @method template for method MethodTagTemplate\HelloWorld::typeAlias() cannot have existing type alias TypeAlias as its name.',
				13,
			],
		]);
	}

}

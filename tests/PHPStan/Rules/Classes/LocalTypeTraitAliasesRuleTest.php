<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<LocalTypeTraitAliasesRule>
 */
class LocalTypeTraitAliasesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new LocalTypeTraitAliasesRule(
			new LocalTypeAliasesCheck(
				['GlobalTypeAlias' => 'int|string'],
				self::createReflectionProvider(),
				$container->getByType(TypeNodeResolver::class),
				new MissingTypehintCheck(true, []),
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
				),
				new UnresolvableTypeHelper(),
				new GenericObjectTypeCheck(),
				true,
				true,
				true,
			),
			self::createReflectionProvider(),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/local-type-trait-aliases.php'], [
			[
				'Type alias ExistingClassAlias already exists as a class in scope of LocalTypeTraitAliases\Bar.',
				23,
			],
			[
				'Type alias GlobalTypeAlias already exists as a global type alias.',
				23,
			],
			[
				'Type alias has an invalid name: int.',
				23,
			],
			[
				'Circular definition detected in type alias RecursiveTypeAlias.',
				23,
			],
			[
				'Circular definition detected in type alias CircularTypeAlias1.',
				23,
			],
			[
				'Circular definition detected in type alias CircularTypeAlias2.',
				23,
			],
			[
				'Cannot import type alias ImportedAliasFromNonClass: class LocalTypeTraitAliases\int does not exist.',
				39,
			],
			[
				'Cannot import type alias ImportedAliasFromUnknownClass: class LocalTypeTraitAliases\UnknownClass does not exist.',
				39,
			],
			[
				'Cannot import type alias ImportedUnknownAlias: type alias does not exist in LocalTypeTraitAliases\Foo.',
				39,
			],
			[
				'Type alias ExistingClassAlias already exists as a class in scope of LocalTypeTraitAliases\Baz.',
				39,
			],
			[
				'Type alias GlobalTypeAlias already exists as a global type alias.',
				39,
			],
			[
				'Imported type alias ExportedTypeAlias has an invalid name: int.',
				39,
			],
			[
				'Type alias OverwrittenTypeAlias overwrites an imported type alias of the same name.',
				39,
			],
			[
				'Circular definition detected in type alias CircularTypeAliasImport2.',
				39,
			],
			[
				'Circular definition detected in type alias CircularTypeAliasImport1.',
				47,
			],
			[
				'Invalid type definition detected in type alias InvalidTypeAlias.',
				62,
			],
			[
				'Trait LocalTypeTraitAliases\MissingType has type alias NoIterablueValue with no value type specified in iterable type array.',
				69,
				'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
			],
		]);
	}

	public function testBug11591(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11591.php'], []);
	}

}

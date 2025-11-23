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
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<LocalTypeAliasesRule>
 */
class LocalTypeAliasesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new LocalTypeAliasesRule(
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
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/local-type-aliases.php'], [
			[
				'Type alias ExistingClassAlias already exists as a class in scope of LocalTypeAliases\Bar.',
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
				'Cannot import type alias ImportedAliasFromNonClass: class LocalTypeAliases\int does not exist.',
				39,
			],
			[
				'Cannot import type alias ImportedAliasFromUnknownClass: class LocalTypeAliases\UnknownClass does not exist.',
				39,
			],
			[
				'Cannot import type alias ImportedUnknownAlias: type alias does not exist in LocalTypeAliases\Foo.',
				39,
			],
			[
				'Type alias ExistingClassAlias already exists as a class in scope of LocalTypeAliases\Baz.',
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
				'Class LocalTypeAliases\MissingTypehints has type alias NoIterableValue with no value type specified in iterable type array.',
				77,
				'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
			],
			[
				'Class LocalTypeAliases\MissingTypehints has type alias NoGenerics with generic class LocalTypeAliases\Generic but does not specify its types: T',
				77,
			],
			[
				'Class LocalTypeAliases\MissingTypehints has type alias NoCallable with no signature specified for callable.',
				77,
			],
			[
				'Type alias A contains unknown class LocalTypeAliases\Nonexistent.',
				87,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Type alias B contains invalid type LocalTypeTraitAliases\Foo.',
				87,
			],
			[
				'Class LocalTypeAliases\Foo referenced with incorrect case: LocalTypeAliases\fOO.',
				87,
			],
			[
				'Type alias A contains unresolvable type.',
				95,
			],
			[
				'Type alias A contains generic type Exception<int> but class Exception is not generic.',
				103,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testEnums(): void
	{
		$this->analyse([__DIR__ . '/data/local-type-aliases-enums.php'], [
			[
				'Cannot import type alias Test: class LocalTypeAliasesEnums\NonexistentClass does not exist.',
				8,
			],
		]);
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<LocalTypeAliasesRule>
 */
class LocalTypeAliasesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new LocalTypeAliasesRule(
			['GlobalTypeAlias' => 'int|string'],
			$this->createReflectionProvider(),
			self::getContainer()->getByType(TypeNodeResolver::class)
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/local-type-aliases.php'], [
			[
				'Type alias ExistingClassAlias already exists as a class in scope of LocalTypeAliases\Bar.',
				22,
			],
			[
				'Type alias GlobalTypeAlias already exists as a global type alias.',
				22,
			],
			[
				'Circular definition detected in type alias RecursiveTypeAlias.',
				22,
			],
			[
				'Circular definition detected in type alias CircularTypeAlias1.',
				22,
			],
			[
				'Circular definition detected in type alias CircularTypeAlias2.',
				22,
			],
			[
				'Cannot import type alias ImportedAliasFromNonClass: class LocalTypeAliases\int does not exist.',
				37,
			],
			[
				'Cannot import type alias ImportedAliasFromUnknownClass: class LocalTypeAliases\UnknownClass does not exist.',
				37,
			],
			[
				'Cannot import type alias ImportedUnknownAlias: type alias does not exist in LocalTypeAliases\Foo.',
				37,
			],
			[
				'Type alias ExistingClassAlias already exists as a class in scope of LocalTypeAliases\Baz.',
				37,
			],
			[
				'Type alias GlobalTypeAlias already exists as a global type alias.',
				37,
			],
			[
				'Type alias OverwrittenTypeAlias overwrites an imported type alias of the same name.',
				37,
			],
			[
				'Circular definition detected in type alias CircularTypeAliasImport2.',
				37,
			],
			[
				'Circular definition detected in type alias CircularTypeAliasImport1.',
				45,
			],
		]);
	}

}

<?php

namespace TypeAliasesDataset;

use function PHPStan\Analyser\assertType;

/**
 * @phpstan-type ExportedTypeAlias \Countable&\Traversable
 */
class Bar
{
}

/**
 * @phpstan-type LocalTypeAlias callable(string $value): (string|false)
 * @phpstan-type NestedLocalTypeAlias LocalTypeAlias[]
 * @phpstan-import-type ExportedTypeAlias from Bar as ImportedTypeAlias
 * @phpstan-type NestedImportedTypeAlias iterable<ImportedTypeAlias>
 */
class Foo
{

	/**
	 * @param GlobalTypeAlias $parameter
	 */
	public function globalAlias($parameter)
	{
		assertType('int|string', $parameter);
	}

	/**
	 * @param LocalTypeAlias $parameter
	 */
	public function localAlias($parameter)
	{
		assertType('callable(string): string|false', $parameter);
	}

	/**
	 * @param NestedLocalTypeAlias $parameter
	 */
	public function nestedLocalAlias($parameter)
	{
		assertType('array<callable(string): string|false>', $parameter);
	}

	/**
	 * @param ImportedTypeAlias $parameter
	 */
	public function importedAlias($parameter)
	{
		assertType('Countable&Traversable', $parameter);
	}

	/**
	 * @param NestedImportedTypeAlias $parameter
	 */
	public function nestedImportedAlias($parameter)
	{
		assertType('iterable<Countable&Traversable>', $parameter);
	}

}

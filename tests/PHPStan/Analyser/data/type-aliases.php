<?php

namespace TypeAliasesDataset\SubScope {
	class Foo
	{
	}

	/**
	 * @phpstan-type ScopedAlias Foo
	 */
	class Bar
	{
	}
}

namespace TypeAliasesDataset {

	use function PHPStan\Analyser\assertType;

	/**
	 * @phpstan-type ExportedTypeAlias \Countable&\Traversable
	 */
	class Bar
	{
	}

	/**
	 * @phpstan-import-type ExportedTypeAlias from Bar as ReexportedTypeAlias
	 */
	class Baz
	{
	}

	/**
	 * @phpstan-type LocalTypeAlias callable(string $value): (string|false)
	 * @phpstan-type NestedLocalTypeAlias LocalTypeAlias[]
	 * @phpstan-import-type ExportedTypeAlias from Bar as ImportedTypeAlias
	 * @phpstan-import-type ReexportedTypeAlias from Baz
	 * @phpstan-type NestedImportedTypeAlias iterable<ImportedTypeAlias>
	 * @phpstan-import-type ScopedAlias from SubScope\Bar
	 * @property GlobalTypeAlias $globalAliasProperty
	 * @property LocalTypeAlias $localAliasProperty
	 * @property ImportedTypeAlias $importedAliasProperty
	 * @property ReexportedTypeAlias $reexportedAliasProperty
	 * @property ScopedAlias $scopedAliasProperty
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

		public function __get(string $name)
		{
			return null;
		}

	}

	assertType('int|string', (new Foo)->globalAliasProperty);
	assertType('callable(string): string|false', (new Foo)->localAliasProperty);
	assertType('Countable&Traversable', (new Foo)->importedAliasProperty);
	assertType('Countable&Traversable', (new Foo)->reexportedAliasProperty);
	assertType('TypeAliasesDataset\SubScope\Foo', (new Foo)->scopedAliasProperty);

}

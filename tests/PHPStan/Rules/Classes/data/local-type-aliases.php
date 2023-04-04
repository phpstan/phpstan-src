<?php

namespace LocalTypeAliases;

class ExistingClassAlias {}

/**
 * @phpstan-type ExportedTypeAlias \Countable&\Traversable
 */
class Foo
{
}

/**
 * @phpstan-type LocalTypeAlias int
 * @phpstan-type ExistingClassAlias \stdClass
 * @phpstan-type GlobalTypeAlias bool
 * @phpstan-type int \stdClass
 * @phpstan-type RecursiveTypeAlias RecursiveTypeAlias[]
 * @phpstan-type CircularTypeAlias1 CircularTypeAlias2
 * @phpstan-type CircularTypeAlias2 CircularTypeAlias1
 */
class Bar
{
}

/**
 * @phpstan-import-type ImportedAliasFromNonClass from int
 * @phpstan-import-type ImportedAliasFromUnknownClass from UnknownClass
 * @phpstan-import-type ImportedUnknownAlias from Foo
 * @phpstan-import-type ExportedTypeAlias from Foo as ExistingClassAlias
 * @phpstan-import-type ExportedTypeAlias from Foo as GlobalTypeAlias
 * @phpstan-import-type ExportedTypeAlias from Foo as OverwrittenTypeAlias
 * @phpstan-import-type ExportedTypeAlias from Foo as int
 * @phpstan-type OverwrittenTypeAlias string
 * @phpstan-import-type CircularTypeAliasImport1 from Qux
 * @phpstan-type CircularTypeAliasImport2 CircularTypeAliasImport1
 */
class Baz
{
}

/**
 * @phpstan-import-type CircularTypeAliasImport2 from Baz
 * @phpstan-type CircularTypeAliasImport1 CircularTypeAliasImport2
 */
class Qux
{
}

/**
 * @phpstan-template T
 * @phpstan-type T never
 */
class Generic
{
}

/**
 * @phpstan-type InvalidTypeAlias invalid-type-definition
 */
class Invalid
{
}

/** @psalm-type MyObject = what{} */
class InvalidTypeDefinitionToIgnoreBecauseItsAParseErrorAlreadyReportedInInvalidPhpDocTagValueRule
{

}

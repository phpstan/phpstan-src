<?php

namespace TraitTemplateType;

/**
 * @template stdClass
 */
trait Foo
{

}

/**
 * @template T of Zazzzu
 */
trait Bar
{

}

/**
 * @template T of float
 */
trait Baz
{

}

/**
 * @phpstan-type ExportedAlias string
 * @template TypeAlias
 */
trait Lorem
{

}

/**
 * @phpstan-type LocalAlias string
 * @phpstan-import-type ExportedAlias from Lorem as ImportedAlias
 * @template LocalAlias
 * @template ExportedAlias
 * @template ImportedAlias
 */
trait Ipsum
{

}

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

/**
 * @template-covariant T
 */
class Dolor
{

}

/**
 * @template T of Dolor<int>
 * @template U of Dolor<covariant int>
 * @template V of Dolor<*>
 * @template W of Dolor<contravariant int>
 */
trait Sit
{

}

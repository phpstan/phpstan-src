<?php

namespace InterfaceTemplateType;

/**
 * @template stdClass
 */
interface Foo
{

}

/**
 * @template T of Zazzzu
 */
interface Bar
{

}

/**
 * @template T of float
 */
interface Baz
{

}

/**
 * @phpstan-type ExportedAlias string
 * @template TypeAlias
 */
interface Lorem
{

}

/**
 * @phpstan-type LocalAlias string
 * @phpstan-import-type ExportedAlias from Lorem as ImportedAlias
 * @template LocalAlias
 * @template ExportedAlias
 * @template ImportedAlias
 */
interface Ipsum
{

}

/** @template T */
interface NormalT
{

}

/** @template T of NormalT<\stdClass>|\stdClass */
interface UnionBound
{

}

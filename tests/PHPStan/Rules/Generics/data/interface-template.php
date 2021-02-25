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
 * @template T of int
 */
interface Baz
{

}

/**
 * @template TypeAlias
 */
interface Lorem
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

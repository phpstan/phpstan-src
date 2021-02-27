<?php

namespace NestedGenericTypesClassCheck;

interface NotGeneric {}

/** @template T */
interface SomeInterface {}

/** @template T of object */
interface SomeObjectInterface {}

/**
 * @template T
 * @template U
 */
interface MultipleGenerics {}

/**
 * @template T
 * @template U of SomeInterface<T>
 */
class Foo
{

}

/**
 * @template T
 * @template U of SomeObjectInterface<T>
 */
class Bar
{

}

/**
 * @template T of int
 * @template U of SomeObjectInterface<T>
 */
class Baz
{

}

/**
 * @template T
 * @template U of NotGeneric<T>
 * @template V of MultipleGenerics<\stdClass>
 * @template W of MultipleGenerics<\stdClass, \Exception, \SplFileInfo>
 */
class Lorem
{

}

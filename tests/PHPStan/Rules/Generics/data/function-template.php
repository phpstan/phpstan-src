<?php

namespace FunctionTemplateType;

/**
 * @template stdClass
 */
function foo()
{

}

/**
 * @template T of Zazzzu
 */
function bar()
{

}

/**
 * @template T of float
 */
function baz()
{

}

/**
 * @template TypeAlias
 */
function lorem()
{

}

/** @template T of bool */
function ipsum()
{

}

/** @template T of float */
function dolor()
{

}

/** @template T of resource */
function resourceBound()
{

}

/** @template T of array */
function izumi()
{

}

/** @template T of array{0: string, 1: bool} */
function nakano()
{

}

/** @template T of null */
function nullNotSupported()
{

}

/** @template T of ?int */
function nullableUnionSupported()
{

}

/** @template T of object{foo: int} */
function objectShapes()
{

}

/** @template-covariant T */
class GenericCovariant {}

/**
 * @template T of GenericCovariant<int>
 * @template U of GenericCovariant<covariant int>
 * @template V of GenericCovariant<*>
 * @template W of GenericCovariant<contravariant int>
 */
function typeProjections()
{

}

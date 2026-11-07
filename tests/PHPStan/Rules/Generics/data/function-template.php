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
function nullSupported()
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

/**
 * @template T = Zazzzu
 */
function invalidDefault()
{

}

/**
 * @template T of object = bool
 */
function outOfBoundsDefault()
{

}

/**
 * @template T
 * @template U = string
 * @template V
 */
function requiredAfterOptional()
{

}

/** @template T of callable */
function callableBound()
{

}

/** @template T of callable(int): string */
function parametrizedCallableBound()
{

}

/** @template T of \Closure */
function closureBound()
{

}

/** @template T of \Closure(int): string */
function parametrizedClosureBound()
{

}

/** @template T of class-string */
function classStringBound()
{

}

/** @template T of class-string<\Exception> */
function genericClassStringBound()
{

}

/** @template T of 1.5 */
function constantFloatBound()
{

}

/** @template T of int<0, 10> */
function integerRangeBound()
{

}

/** @template T of void */
function voidBound()
{

}

/** @template T of never */
function neverBound()
{

}

/** @template T of true */
function trueBound()
{

}

/** @template T of false */
function falseBound()
{

}

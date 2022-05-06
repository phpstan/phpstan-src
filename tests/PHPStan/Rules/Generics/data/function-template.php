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

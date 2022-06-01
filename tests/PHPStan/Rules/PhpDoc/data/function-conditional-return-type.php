<?php

namespace FunctionConditionalReturnType;

/**
 * @return ($i is positive-int ? non-empty-array : array)
 */
function fill(int $i): array
{

}

/**
 * @template T
 * @param T $p
 * @return (T is positive-int ? non-empty-array : array)
 */
function fill2(int $i): array
{

}

/**
 * @template T of int
 * @param T $p
 * @return (T is positive-int ? non-empty-array : array)
 */
function fill3(int $i): array
{

}

/**
 * @param int $i
 * @return (\stdClass is object ? Foo : Bar)
 */
function doFoo(int $i)
{

}

/**
 * @return ($j is object ? Foo : Bar)
 */
function doFoo3(int $i)
{

}

/**
 * @return ($i is int ? non-empty-array : array)
 */
function fill4(int $i): array
{

}

/**
 * @template T of int
 * @param T $p
 * @return (T is int ? non-empty-array : array)
 */
function fill5(int $i): array
{

}

/**
 * @template T of int
 * @param T $p
 * @return (T is int ? non-empty-array : array)
 */
function fill6($i): array
{

}

/**
 * @return ($i is not int ? non-empty-array : array)
 */
function fill7(int $i): array
{

}

/**
 * @return ($i is string ? non-empty-array : array)
 */
function fill8(int $i): array
{

}

/**
 * @template T of int
 * @param T $p
 * @return (T is string ? non-empty-array : array)
 */
function fill9(int $i): array
{

}

/**
 * @template T of int
 * @param T $p
 * @return (T is string ? non-empty-array : array)
 */
function fill10($i): array
{

}

/**
 * @return ($i is not string ? non-empty-array : array)
 */
function fill11(int $i): array
{

}

/**
 * @return ($i is int ? non-empty-array : array)
 */
function fill12(int ...$i): array
{

}

/**
 * @return ($i is array<int> ? non-empty-array : array)
 */
function fill13(int ...$i): array
{

}

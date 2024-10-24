<?php

namespace FunctionAssert;

/**
 * @phpstan-assert int $i
 */
function fill(int $i): void
{
}

/**
 * @template T
 * @param T $p
 * @phpstan-assert int $i
 */
function fill2(int $i): array
{
}

/**
 * @template T of int
 * @param T $p
 * @phpstan-assert int $i
 */
function fill3(int $i): array
{
}

/**
 * @template T of int
 * @param T $p
 * @phpstan-assert positive-int $i
 */
function fill4(int $i): array
{
}

/**
 * @phpstan-assert int|string $i
 */
function fill5(int $i): void
{
}

/**
 * @phpstan-assert string $i
 */
function fill6(int $i): void
{
}

/**
 * @phpstan-assert positive-int $j
 */
function doFoo(int $i)
{
}

/**
 * @phpstan-assert !int $i
 */
function negate(int $i): void
{
}

/**
 * @phpstan-assert !string $i
 */
function negate1(int $i): void
{
}
/**
 * @phpstan-assert !positive-int $i
 */
function negate2(int $i): void
{
}

/**
 * @param array<mixed> $array
 * @param string $message
 *
 * @phpstan-impure
 *
 * @psalm-assert list $array
 */
function isList($array, $message = ''): void
{
	if (!array_is_list($array)) {

	}
}

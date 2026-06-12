<?php declare(strict_types = 1); // lint >= 8.0

namespace FunctionConditionalReturnType;

/**
 * @template T
 * @return ($id is class-string<T> ? T : mixed)
 */
function get(string $id): mixed
{
}

/**
 * @template T
 * @return ($id is not class-string<T> ? T : mixed)
 */
function notGet(string $id): mixed
{
}

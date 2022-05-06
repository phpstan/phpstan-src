<?php declare(strict_types = 1);

namespace Bug5706;

/**
 * @template T of string|int
 * @param T $key
 * @return T
 */
function bar($key)
{
    if (is_int($key)) {
        return $key;
    }

    // String
    return $key;
}

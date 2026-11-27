<?php declare(strict_types = 1);

namespace Bug8963;

/**
 * @param array<string>|array<int> $array
 */
function test(array $array): void
{
}

$array = ['a', 2, 'c'];
test($array);

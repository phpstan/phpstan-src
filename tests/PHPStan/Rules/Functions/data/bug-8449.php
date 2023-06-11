<?php declare(strict_types = 1);

namespace Bug8449;

/** @var list<int> $a */
$a = [1];
/** @var list<int> $b */
$b = [2];

array_push($a, ...$b);

/**
 * @param list<int> $parameter
 */
function test(array $parameter): void
{
}

test($a);

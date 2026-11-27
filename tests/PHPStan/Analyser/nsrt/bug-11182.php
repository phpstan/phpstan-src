<?php declare(strict_types = 1);

namespace Bug11182;

use function PHPStan\Testing\assertType;

/**
 * @template Tv
 * @template Ts
 *
 * @param iterable<Tv> $iterable Iterable to be mapped over
 * @param (callable(Tv): iterable<Ts>) $mapper
 *
 * @return list<Ts>
 */
function flat_map(iterable $iterable, callable $mapper): array
{
    $flattened = [];
    foreach ($iterable as $value) {
        foreach ($mapper($value) as $item) {
            $flattened[] = $item;
        }
    }

    return $flattened;
}


$rows = [
	['id' => 1],
	['id' => 2],
];

$ids = flat_map($rows, function ($row) {
	$result = [$row['id']];
	assertType('array{1|2}', $result);
	return $result;
});

assertType('list<1|2>', $ids);

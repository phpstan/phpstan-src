<?php declare(strict_types = 1);

namespace Bug7185;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue of object
 * @extends \IteratorAggregate<TKey, TValue>
 */
interface Collection extends \IteratorAggregate {}

function foo(Collection $list): void {
	$all = iterator_to_array($list);
	assertType('array<object>', $all);
	assertType('object|false', current($all));
}

<?php

namespace Bug2911;

/**
 * @param array<mixed> $array
 */
function foo(array $array): void {
	$array['bar'] = 'string';

	// 'bar' is always set, should not complain here
	bar($array);
}


/**
 * @param array<mixed> $array
 */
function foo2(array $array): void {
	$array['foo'] = 'string';

	// 'bar' is always set, should not complain here
	bar($array);
}


/**
 * @param array{bar: string} $array
 */
function bar(array $array): void {
}

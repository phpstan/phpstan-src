<?php

namespace Bug2568;

/**
 * @template T
 *
 * @param array<T, mixed> $arr
 * @return array<int, T>
 */
function my_array_keys($arr) {
	return array_keys($arr);
}

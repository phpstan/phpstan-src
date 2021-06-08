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

/**
 * @template T of array-key
 *
 * @param array<T, mixed> $arr
 * @return array<int, T>
 */
function my_array_keys2($arr) {
	return array_keys($arr);
}

/**
 * @template T of int|string
 *
 * @param array<T, mixed> $arr
 * @return array<int, T>
 */
function my_array_keys3($arr) {
	return array_keys($arr);
}

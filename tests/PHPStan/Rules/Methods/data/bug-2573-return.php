<?php

namespace Bug2573;

class Bar
{

	/**
	 * @template V
	 * @template D
	 *
	 * @param array<V> $array
	 * @param array-key $key
	 * @param D $default
	 * @return V|D
	 */
	function idx($array, $key, $default = null) {
		if (array_key_exists($key, $array)) {
			return $array[$key];
		}
		return $default;
	}

	/**
	 * @param array<int, int> $arr
	 * @return int
	 */
	function example($arr) {
		return $this->idx($arr, 5, 42);
	}

}

<?php

namespace Bug5128;

/**
 * @param array{a: string}|array{b: string} $array
 */
function a(array $array): string {
	if(isset($array['a'])){
		return $array['a'];
	}

	return $array['b'];
}

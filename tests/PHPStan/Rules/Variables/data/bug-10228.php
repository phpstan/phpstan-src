<?php

namespace Bug10228;

function doFoo() {
	$bool = 1 == random_int(1, 2)
		&& 2 == random_int(1, 2)
		&& in_array(
			1,
			$var = [random_int(1, 2)]
		);

	if ($bool) {
		// here $bool is true
		// so $var must be defined
		// because the 3rd condition above must have been fulfilled for the $bool to be true
		// so 2nd argument to in_array() must have been computed
		throw new \Exception($var);
	}
}

<?php declare(strict_types=1);

namespace Bug2851;

function doFoo() {
	$arguments = ['x', 'y'];

	$words = '';
	while (count($arguments) > 0) {
		$words .= array_pop($arguments);
		if (count($arguments) > 0) {
			$words .= ' ';
		}
	}

	echo $words;
}

<?php

namespace Bug8643;

function (): void {
	$x = 0;

	while ($x < 4) {
		$y = 0;

		while ($y < 6) {
			$y += 1;
		}

		$x += 1;
	}

	echo 'done';
};

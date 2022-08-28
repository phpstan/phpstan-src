<?php

namespace Bug6599;

function (): void {
	$currentStrategy = 10;
	$allowedStrategies = array(10, 20);

	if (rand(0, 1) === 0) {
		$currentStrategy = 10;
		$allowedStrategies = array(10);
	} elseif (rand(0, 1) === 0) {
		$currentStrategy = 20;
		$allowedStrategies = array(20);
	}

	if (10 === $currentStrategy && PHP_OS_FAMILY === 'Windows') {
		if (!in_array(20, $allowedStrategies, true)) {
			throw new \RuntimeException('You are on an old Windows / old PHP combo which does not allow Composer to use junctions/symlinks and this path repository has symlink:true in its options so copying is not allowed');
		}
		$currentStrategy = 20;
		$allowedStrategies = array(20);
	}
};

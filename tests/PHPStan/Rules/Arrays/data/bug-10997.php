<?php

namespace Bug10997;

function (): void {
	$array = [1, 2, 3, 4];
	for ($i = 0; $i < count($array); ++$i) {
		echo $array[$i], "\n";
	}
};

function (): void {
	$array = [1, 2, 3, 4];
	for ($i = 0; $i < 5; ++$i) {
		echo $array[$i], "\n";
	}
};

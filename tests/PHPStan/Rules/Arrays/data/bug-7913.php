<?php declare(strict_types = 1);

namespace Bug7913;

const X = [];
if (!empty(X)) {
	foreach (X as $y) {
		print($y);
	}
}

$x = [];
if (!empty($x)) {
	foreach ($x as $y) {
		print($y);
	}
}

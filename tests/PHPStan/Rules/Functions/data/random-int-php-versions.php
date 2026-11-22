<?php // lint >= 8.0

if (PHP_VERSION_ID < 80000) {
	random_int(max: 0, min: 1);
}

if (PHP_VERSION_ID >= 80000) {
	random_int(max: 0, min: 1);
}

random_int(max: 0, min: 1);

<?php

namespace Bug7555;

$a = 'ab';

if (substr($a, 0, PHP_INT_MAX) === 'x') {
	exit(1);
}

if (strlen($a) === 2) {
	echo('According to phpstan, this should not print because if will always evaluate to false');
}

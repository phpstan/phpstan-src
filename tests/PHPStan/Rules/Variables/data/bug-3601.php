<?php declare(strict_types = 1);

namespace Bug3601;

if (rand(0, 1)) {
	$a = 'b';
}

if (rand(0, 1)) {
	$c = ['b' => 'everything is fine'];
}

if (isset($a, $c, $c[$a])) {
	echo $c[$a];
}

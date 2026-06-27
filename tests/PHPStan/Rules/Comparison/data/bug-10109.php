<?php declare(strict_types = 1);

namespace Bug10109;

// simpler
$x = 5;
while (--$x > 0) {
	echo "$x\n";
}
if ($x === 0) {
	echo "zero\n";
}

// closer to real codebase
$x = 5;
while (mt_rand(0, 10) < 10 && --$x > 0) {
	echo "$x\n";
}
if ($x === 0) {
	echo "zero\n";
}

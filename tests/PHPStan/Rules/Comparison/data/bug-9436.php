<?php // lint >= 8.0

namespace Bug9436;

$foo = rand(0, 100);

if (!in_array($foo, [0, 1, 2])) {
	exit();
}

$bar = match ($foo) {
	0 => 'a',
	1 => 'b',
	2 => 'c',
};

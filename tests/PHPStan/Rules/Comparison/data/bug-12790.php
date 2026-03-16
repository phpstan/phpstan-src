<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12790;

$r = [];
$r[] = 'a';
if (rand(0, 1)) {
	$r[] = 'b';
}

echo match (count($r)) {
	1 => 'one',
	2 => 'two',
};

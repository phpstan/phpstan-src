<?php

namespace Bug6112;

$x = (bool) mt_rand(0, 1);
$y = mt_rand(0, 2);
if ($y === 2) {
	$y = new \PDO('');
} else {
	$y = (bool) $y;
}
$z = (bool) mt_rand(0, 1);

if (($x && ($v = $y)                   ) || $v = $z) {
	var_dump($v);
}

while (($x && ($w = $y) instanceof \PDO) || $w = $z) {
	var_dump($w);
}

<?php
declare(strict_types=1);

namespace Bug7230;

error_reporting(E_ALL);

$tmp = '';
$tmp2= '';
$counter = 0;
while(++$counter < 100) {
	$tmp = 'tmp_' . (string)$counter;
	$tmp2= $tmp;
}
if ($counter === 100) {
	$tmp = 'tmp_hardcoded';
}

var_dump($tmp);
var_dump($tmp2);
var_dump($tmp === 'tmp_hardcoded');
var_dump($tmp === 'tmp_100');

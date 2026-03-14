<?php declare(strict_types = 1);

namespace Bug8056Rule;

$array = [];
$tmp = &$array;
$tmp[] = 'foo';

foreach ($array as $i) {

}

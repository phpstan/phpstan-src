<?php

declare(strict_types=1);

class X {}

$x = new X;
$x['invalidoffset'][0] = [
	'foo' => 'bar'
];

$arr = ['a' => ['b' => [5]]];
var_dump($arr['invalid']['c']);
var_dump($arr['a']['invalid']);

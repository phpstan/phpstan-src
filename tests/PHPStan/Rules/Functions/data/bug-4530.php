<?php

namespace Bug4530;

$array1 = array("a" => "green", "b" => "brown", "c" => "blue", "red");
$array2 = array("a" => "GREEN", "B" => "brown", "yellow", "red");

array_uintersect($array1, $array2, "strcasecmp");

<?php

namespace Bug13714;

// the following examples don't make much sense code-wise.
// they are here as they triggered a PHPStan crash.

$ch = curl_init();

function curl_setopt():void {}
curl_setopt($ch, CURLOPT_HEADER, false);

function curl_setopt_array():void {}
$options = [];
curl_setopt_array($ch, $options);

function implode():void {}
$array = ['lastname', 'email', 'phone'];
implode(",", $array);

function array_map():void {}
$a = [1, 2, 3, 4, 5];
array_map('cube', $a);

function array_filter():void {}
$array2 = [6, 7, 8, 9, 10, 11, 12];
array_filter($array2, "odd");

function array_walk():void {}
$fruits = array("d" => "lemon", "a" => "orange", "b" => "banana", "c" => "apple");
function test_print(string $item2, string $key):void
{
	echo "$key. $item2\n";
}
array_walk($fruits, 'test_print');

$array = [
	'a' => 'dog',
	'b' => 'cat',
	'c' => 'cow',
	'd' => 'duck',
	'e' => 'goose',
	'f' => 'elephant'
];
function array_find():void {}
array_find($array, function (string $value) {
	return strlen($value) > 4;
});

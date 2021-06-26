<?php // lint >= 7.4

namespace Bug3660;

function (): void {
	$arr = [1,2,3,4];
	array_map(fn($str) => strlen($str), $arr);
	array_map(function($str) { return strlen($str);} ,$arr);
};

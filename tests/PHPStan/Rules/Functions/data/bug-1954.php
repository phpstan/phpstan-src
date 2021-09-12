<?php

namespace Bug1954;

function (): void {
	$a = [1, new \stdClass()];
	$b = array_map(function (string $s) : string { return $s; }, $a);
};

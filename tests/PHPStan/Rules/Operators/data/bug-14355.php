<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14355;

/** @var array<int, string[]|string> $data */
$data = [2 => "123", 3 => ["1", "2" , "3"]];

$data = array_filter($data, \ctype_digit(...));

$d = 2;
if (is_array($data[$d]) && (count($data[$d]) > 1)) {
	var_dump("case");
}

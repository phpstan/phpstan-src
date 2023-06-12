<?php declare(strict_types = 1);

namespace Bug7580\Comparison;

print_r(mb_str_split('', 1));
print_r(mb_str_split('', 1) ?: ['']);

print_r(mb_str_split('x', 1));
print_r(mb_str_split('x', 1) ?: ['']);

$v = (string) (mt_rand() === 0 ? '' : 'x');
\PHPStan\dumpType($v);
print_r(mb_str_split($v, 1));
print_r(mb_str_split($v, 1) ?: ['']); // there must be no phpstan error for this line

function x(): string { throw new \Exception(); };
$v = x();
\PHPStan\dumpType($v);
print_r(mb_str_split($v, 1));
print_r(mb_str_split($v, 1) ?: ['']); // there must be no phpstan error for this line

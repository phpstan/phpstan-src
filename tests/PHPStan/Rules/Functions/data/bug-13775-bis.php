<?php declare(strict_types = 1);

$a = [null];
echo 'null, valid cast: ' . array_sum($a) . "\n\n";

$a = [false];
echo 'false, valid cast: ' . array_sum($a) . "\n\n";

$a = [true];
echo 'true, valid cast: ' . array_sum($a) . "\n\n";

$a = [''];
echo 'empty string, invalid cast: ' . array_sum($a) . "\n\n";

$a = ['42.5'];
echo 'string of a float, valid cast: ' . array_sum($a) . "\n\n";

$a = ['42,5'];
echo 'string of comma-separated float, valid cast but not desirable (discards trailing chars): ' . array_sum($a) . "\n\n";

$a = ['42a'];
echo 'string of int with trailing alpha char, valid cast but not desirable (discards trailing chars): ' . array_sum($a) . "\n\n";

$a = ['a42'];
echo 'string of int with leading alpha char, invalid cast: ' . array_sum($a) . "\n\n";

$a = [[]];
echo 'array, invalid cast: ' . array_sum($a) . "\n\n";

$a = [new stdClass()];
echo 'class that does not auto-cast, invalid cast: ' . array_sum($a) . "\n\n";

$a = [rand(0, 1) ? new stdClass() : gmp_init(42)];
echo 'possibly invalid cast: ' . array_sum($a) . "\n\n";

$a = [gmp_init(42)];
echo 'gmp, valid cast: ' . array_sum($a) . "\n\n";

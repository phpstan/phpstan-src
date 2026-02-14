<?php declare(strict_types = 1);

/** @var mixed $a */
$a = doFoo();
echo array_sum([$a]) . "\n\n";
echo array_product([$a]) . "\n\n";

$b = rand(0, 1) ? 42 : '';
echo array_sum([$b]) . "\n\n";
echo array_product([$b]) . "\n\n";

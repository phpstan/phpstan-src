<?php declare(strict_types = 1);

namespace Bug14508;

use function PHPStan\dumpType;

$bool_set_before = (bool) random_int(0, 1);
$int_set_after = random_int(0, 100);

dumpType($int_set_after, $bool_set_before);

<?php declare(strict_types = 1);

namespace Bug14508PhpDoc;

use function PHPStan\dumpPhpDocType;

$bool_set_before = (bool) random_int(0, 1);
$int_set_after = random_int(0, 100);

dumpPhpDocType($int_set_after, $bool_set_before);

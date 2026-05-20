<?php declare(strict_types = 1);

use function PHPStan\Testing\assertType;

// top-level forward goto
$id = null;
if (random_int(0, 1))
	goto fin;
$id = 1;
fin:
assertType('1|null', $id);

// top-level backward goto
$ok = false;
retry:
assertType('bool', $ok);
if (!$ok) {
	$ok = (bool) random_int(0, 1);
	goto retry;
}

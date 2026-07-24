<?php

declare(strict_types=1);

namespace Bug14985;

ob_start();
$a = ob_get_clean();

// There is no check whether ob_start() was successful, so the if condition cannot be guaranteed to be always false, as PHPStan claims.
if ($a === false) {
	echo "false";
}



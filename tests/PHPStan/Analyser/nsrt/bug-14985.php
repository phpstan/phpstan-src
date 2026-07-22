<?php declare(strict_types = 1);

namespace Bug14985;

use function PHPStan\Testing\assertType;

function reproduce(): void
{
	// ob_start()'s success is not checked, so the buffer may not be active
	// and ob_get_clean() may return false.
	ob_start();
	$a = ob_get_clean();
	assertType('string|false', $a);
	if ($a === false) {
		echo 'false';
	}
}

function checkedObStart(): void
{
	if (!ob_start()) {
		return;
	}
	// here ob_start() is known to have succeeded, so the buffer is active
	assertType('string', ob_get_clean());
}

<?php declare(strict_types = 1);

namespace Bug14469Nsrt;

use function PHPStan\Testing\assertType;

function t(array $R, bool $var1, object $user): void {
	$aa = null;

	if ($var1) {
		$aa = $user->id  === 10 ? 2 : null;
	} elseif ($R['aa']) {
		$aa = $R['aa'];
	}

	if ($aa) {
		assertType('mixed', $R['aa']);
	}
}

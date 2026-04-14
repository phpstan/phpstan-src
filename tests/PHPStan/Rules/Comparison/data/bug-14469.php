<?php declare(strict_types = 1);

namespace Bug14469;

function t(array $R, bool $var1, object $user, bool $is): array {
	$aa = null;

	if ($var1) {
		$aa = $user->id  === 10 ? 2 : null;
	} elseif ($R['aa']) {
		$aa = $R['aa'];
	}

	if ($aa) {
		if (!$R['aa']) {
			return [];
		}
	}
	return $R;
}

function t2(array $R, bool $var1, object $user): array {
	$aa = null;

	if ($var1) {
		$aa = $user->id  === 10 ? 2 : null;
	} elseif ($R['aa']) {
		$aa = $R['aa'];
	}

	if ($aa) {
		if ($R['aa'] === false) {
			return [];
		}
	}
	return $R;
}

function t3(array $R, bool $var1, int $other): array {
	$aa = null;

	if ($var1) {
		$aa = $other;
	} elseif ($R['bb']) {
		$aa = $R['bb'];
	}

	if ($aa) {
		if (!$R['bb']) {
			return [];
		}
	}
	return $R;
}

function t4(array $R, bool $var1, object $user): string {
	$aa = null;

	if ($var1) {
		$aa = $user->id  === 10 ? 2 : null;
	} elseif ($R['aa']) {
		$aa = $R['aa'];
	}

	return $aa ? ($R['aa'] ? 'yes' : 'no') : 'none';
}

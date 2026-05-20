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

function testIfConstantCondition(array $R, bool $var1, object $user): void {
    $aa = null;

    if ($var1) {
        $aa = $user->id === 10 ? 2 : null;
    } elseif ($R['aa']) {
        $aa = $R['aa'];
    }

    if ($aa) {
        if ($R['aa']) {
            // not always true
        }
    }
}

function testFalseyBranch(array $R, bool $var1, object $user): void {
    $bb = 'default';

    if ($var1) {
        $bb = $user->id === 10 ? null : 'active';
    } elseif (!$R['bb']) {
        $bb = $R['bb'];
    }

    if (!$bb) {
        if ($R['bb']) {
            return;
        }
    }
}

function testWithElse(array $R, bool $var1, object $user): void {
    $aa = null;

    if ($var1) {
        $aa = $user->id === 10 ? 2 : null;
    } elseif ($R['aa']) {
        $aa = $R['aa'];
    } else {
        $aa = 0;
    }

    if ($aa) {
        if (!$R['aa']) {
            return;
        }
    }
}

function testMultipleElseif(array $R, bool $var1, bool $var2, object $user): void {
    $aa = null;

    if ($var1) {
        $aa = $user->id === 10 ? 2 : null;
    } elseif ($var2) {
        $aa = 42;
    } elseif ($R['aa']) {
        $aa = $R['aa'];
    }

    if ($aa) {
        if (!$R['aa']) {
            return;
        }
    }
}

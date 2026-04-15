<?php

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

/** Property fetch variant */
function propertyFetch(object $obj, bool $var1, object $user): void {
    $aa = null;

    if ($var1) {
        $aa = $user->id === 10 ? 2 : null;
    } elseif ($obj->prop) {
        $aa = $obj->prop;
    }

    if ($aa) {
        if (!$obj->prop) {
            return;
        }
    }
}

/** Nested array fetch variant */
function nestedArrayFetch(array $R, bool $var1, object $user): void {
    $aa = null;

    if ($var1) {
        $aa = $user->id === 10 ? 2 : null;
    } elseif ($R['a']['b']) {
        $aa = $R['a']['b'];
    }

    if ($aa) {
        if (!$R['a']['b']) {
            return;
        }
    }
}

/** Multiple elseif branches */
function multipleElseif(array $R, bool $var1, bool $var2, object $user): void {
    $aa = null;

    if ($var1) {
        $aa = $user->id === 10 ? 2 : null;
    } elseif ($var2) {
        $aa = 5;
    } elseif ($R['aa']) {
        $aa = $R['aa'];
    }

    if ($aa) {
        if (!$R['aa']) {
            return;
        }
    }
}

/**
 * Variable equivalent: pre-defined variable used in elseif condition.
 * Same pattern as the array dim fetch case but $bb is a Variable defined
 * before the if/elseif, so it's present in both branches' expression types.
 * The existing guard-overlap check (lines that test array_key_exists($exprString,
 * $theirExpressionTypes)) handles this case correctly.
 */
function variableEquivalent(bool $var1, object $user, mixed $input): void {
	$aa = null;
	$bb = $input;

	if ($var1) {
		$aa = $user->id === 10 ? 2 : null;
	} elseif ($bb) {
		$aa = $bb;
	}

	if ($aa) {
		if (!$bb) {
			return;
		}
	}
}

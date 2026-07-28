<?php // lint >= 8.0

namespace UnionFastPath;

/**
 * Exercises TypeCombinator::union() fast path for trivial 2-type cases.
 *
 * Each early-return branch produces union(never, X) during scope merging.
 * Each if/else with unchanged variables produces union(X, X).
 * The many mixed parameters produce union(mixed, X) during narrowing.
 */

/** @param array<string, mixed> $d */
function narrowAndBranch(array $d, mixed $m1, mixed $m2, mixed $m3, mixed $m4): string
{
	if (!is_string($m1)) { return ''; }
	if (!is_string($m2)) { return ''; }
	if (!is_string($m3)) { return ''; }
	if (!is_string($m4)) { return ''; }

	$a = $b = $c = $d1 = $e = $f = $g = $h = '';

	if (isset($d['a'])) { $a = (string) $d['a']; } else { return ''; }
	if (isset($d['b'])) { $b = (string) $d['b']; } else { return ''; }
	if (isset($d['c'])) { $c = (string) $d['c']; } else { return ''; }
	if (isset($d['d'])) { $d1 = (string) $d['d']; } else { return ''; }
	if (isset($d['e'])) { $e = (string) $d['e']; } else { return ''; }
	if (isset($d['f'])) { $f = (string) $d['f']; } else { return ''; }
	if (isset($d['g'])) { $g = (string) $d['g']; } else { return ''; }
	if (isset($d['h'])) { $h = (string) $d['h']; } else { return ''; }

	return $a . $b . $c . $d1 . $e . $f . $g . $h . $m1 . $m2 . $m3 . $m4;
}

/** @param array<string, mixed> $d */
function narrowAndBranch2(array $d, mixed $m1, mixed $m2, mixed $m3, mixed $m4): string
{
	if (!is_string($m1)) { return ''; }
	if (!is_string($m2)) { return ''; }
	if (!is_string($m3)) { return ''; }
	if (!is_string($m4)) { return ''; }

	$a = $b = $c = $d1 = $e = $f = $g = $h = '';

	if (isset($d['a1'])) { $a = (string) $d['a1']; } else { return ''; }
	if (isset($d['b1'])) { $b = (string) $d['b1']; } else { return ''; }
	if (isset($d['c1'])) { $c = (string) $d['c1']; } else { return ''; }
	if (isset($d['d1'])) { $d1 = (string) $d['d1']; } else { return ''; }
	if (isset($d['e1'])) { $e = (string) $d['e1']; } else { return ''; }
	if (isset($d['f1'])) { $f = (string) $d['f1']; } else { return ''; }
	if (isset($d['g1'])) { $g = (string) $d['g1']; } else { return ''; }
	if (isset($d['h1'])) { $h = (string) $d['h1']; } else { return ''; }

	return $a . $b . $c . $d1 . $e . $f . $g . $h . $m1 . $m2 . $m3 . $m4;
}

/** @param array<string, mixed> $d */
function narrowAndBranch3(array $d, mixed $m1, mixed $m2, mixed $m3, mixed $m4): string
{
	if (!is_string($m1)) { return ''; }
	if (!is_string($m2)) { return ''; }
	if (!is_string($m3)) { return ''; }
	if (!is_string($m4)) { return ''; }

	$a = $b = $c = $d1 = $e = $f = $g = $h = '';

	if (isset($d['a2'])) { $a = (string) $d['a2']; } else { return ''; }
	if (isset($d['b2'])) { $b = (string) $d['b2']; } else { return ''; }
	if (isset($d['c2'])) { $c = (string) $d['c2']; } else { return ''; }
	if (isset($d['d2'])) { $d1 = (string) $d['d2']; } else { return ''; }
	if (isset($d['e2'])) { $e = (string) $d['e2']; } else { return ''; }
	if (isset($d['f2'])) { $f = (string) $d['f2']; } else { return ''; }
	if (isset($d['g2'])) { $g = (string) $d['g2']; } else { return ''; }
	if (isset($d['h2'])) { $h = (string) $d['h2']; } else { return ''; }

	return $a . $b . $c . $d1 . $e . $f . $g . $h . $m1 . $m2 . $m3 . $m4;
}

/** @param array<string, mixed> $d */
function narrowAndBranch4(array $d, mixed $m1, mixed $m2, mixed $m3, mixed $m4): string
{
	if (!is_string($m1)) { return ''; }
	if (!is_string($m2)) { return ''; }
	if (!is_string($m3)) { return ''; }
	if (!is_string($m4)) { return ''; }

	$a = $b = $c = $d1 = $e = $f = $g = $h = '';

	if (isset($d['a3'])) { $a = (string) $d['a3']; } else { return ''; }
	if (isset($d['b3'])) { $b = (string) $d['b3']; } else { return ''; }
	if (isset($d['c3'])) { $c = (string) $d['c3']; } else { return ''; }
	if (isset($d['d3'])) { $d1 = (string) $d['d3']; } else { return ''; }
	if (isset($d['e3'])) { $e = (string) $d['e3']; } else { return ''; }
	if (isset($d['f3'])) { $f = (string) $d['f3']; } else { return ''; }
	if (isset($d['g3'])) { $g = (string) $d['g3']; } else { return ''; }
	if (isset($d['h3'])) { $h = (string) $d['h3']; } else { return ''; }

	return $a . $b . $c . $d1 . $e . $f . $g . $h . $m1 . $m2 . $m3 . $m4;
}

/** @param array<string, mixed> $d */
function narrowAndBranch5(array $d, mixed $m1, mixed $m2, mixed $m3, mixed $m4): string
{
	if (!is_string($m1)) { return ''; }
	if (!is_string($m2)) { return ''; }
	if (!is_string($m3)) { return ''; }
	if (!is_string($m4)) { return ''; }

	$a = $b = $c = $d1 = $e = $f = $g = $h = '';

	if (isset($d['a4'])) { $a = (string) $d['a4']; } else { return ''; }
	if (isset($d['b4'])) { $b = (string) $d['b4']; } else { return ''; }
	if (isset($d['c4'])) { $c = (string) $d['c4']; } else { return ''; }
	if (isset($d['d4'])) { $d1 = (string) $d['d4']; } else { return ''; }
	if (isset($d['e4'])) { $e = (string) $d['e4']; } else { return ''; }
	if (isset($d['f4'])) { $f = (string) $d['f4']; } else { return ''; }
	if (isset($d['g4'])) { $g = (string) $d['g4']; } else { return ''; }
	if (isset($d['h4'])) { $h = (string) $d['h4']; } else { return ''; }

	return $a . $b . $c . $d1 . $e . $f . $g . $h . $m1 . $m2 . $m3 . $m4;
}

function deepSwitch(mixed $val, mixed $a, mixed $b, mixed $c, mixed $d, mixed $e, mixed $f): string
{
	if (!is_string($a)) { return ''; }
	if (!is_string($b)) { return ''; }
	if (!is_string($c)) { return ''; }
	if (!is_string($d)) { return ''; }
	if (!is_string($e)) { return ''; }
	if (!is_string($f)) { return ''; }

	$r = '';
	if ($val === 'x1') { $r = $a; }
	elseif ($val === 'x2') { $r = $b; }
	elseif ($val === 'x3') { $r = $c; }
	elseif ($val === 'x4') { $r = $d; }
	elseif ($val === 'x5') { $r = $e; }
	elseif ($val === 'x6') { $r = $f; }
	elseif ($val === 'x7') { $r = $a . $b; }
	elseif ($val === 'x8') { $r = $c . $d; }
	elseif ($val === 'x9') { $r = $e . $f; }
	elseif ($val === 'x10') { $r = $a . $c; }
	elseif ($val === 'x11') { $r = $b . $d; }
	elseif ($val === 'x12') { $r = $c . $e; }
	elseif ($val === 'x13') { $r = $d . $f; }
	elseif ($val === 'x14') { $r = $a . $e; }
	elseif ($val === 'x15') { $r = $b . $f; }
	else { $r = $a . $b . $c . $d . $e . $f; }

	return $r;
}

function deepSwitch2(mixed $val, mixed $a, mixed $b, mixed $c, mixed $d, mixed $e, mixed $f): string
{
	if (!is_string($a)) { return ''; }
	if (!is_string($b)) { return ''; }
	if (!is_string($c)) { return ''; }
	if (!is_string($d)) { return ''; }
	if (!is_string($e)) { return ''; }
	if (!is_string($f)) { return ''; }

	$r = '';
	if ($val === 'y1') { $r = $a; }
	elseif ($val === 'y2') { $r = $b; }
	elseif ($val === 'y3') { $r = $c; }
	elseif ($val === 'y4') { $r = $d; }
	elseif ($val === 'y5') { $r = $e; }
	elseif ($val === 'y6') { $r = $f; }
	elseif ($val === 'y7') { $r = $a . $b; }
	elseif ($val === 'y8') { $r = $c . $d; }
	elseif ($val === 'y9') { $r = $e . $f; }
	elseif ($val === 'y10') { $r = $a . $c; }
	elseif ($val === 'y11') { $r = $b . $d; }
	elseif ($val === 'y12') { $r = $c . $e; }
	elseif ($val === 'y13') { $r = $d . $f; }
	elseif ($val === 'y14') { $r = $a . $e; }
	elseif ($val === 'y15') { $r = $b . $f; }
	else { $r = $a . $b . $c . $d . $e . $f; }

	return $r;
}

function deepSwitch3(mixed $val, mixed $a, mixed $b, mixed $c, mixed $d, mixed $e, mixed $f): string
{
	if (!is_string($a)) { return ''; }
	if (!is_string($b)) { return ''; }
	if (!is_string($c)) { return ''; }
	if (!is_string($d)) { return ''; }
	if (!is_string($e)) { return ''; }
	if (!is_string($f)) { return ''; }

	$r = '';
	if ($val === 'z1') { $r = $a; }
	elseif ($val === 'z2') { $r = $b; }
	elseif ($val === 'z3') { $r = $c; }
	elseif ($val === 'z4') { $r = $d; }
	elseif ($val === 'z5') { $r = $e; }
	elseif ($val === 'z6') { $r = $f; }
	elseif ($val === 'z7') { $r = $a . $b; }
	elseif ($val === 'z8') { $r = $c . $d; }
	elseif ($val === 'z9') { $r = $e . $f; }
	elseif ($val === 'z10') { $r = $a . $c; }
	elseif ($val === 'z11') { $r = $b . $d; }
	elseif ($val === 'z12') { $r = $c . $e; }
	elseif ($val === 'z13') { $r = $d . $f; }
	elseif ($val === 'z14') { $r = $a . $e; }
	elseif ($val === 'z15') { $r = $b . $f; }
	else { $r = $a . $b . $c . $d . $e . $f; }

	return $r;
}

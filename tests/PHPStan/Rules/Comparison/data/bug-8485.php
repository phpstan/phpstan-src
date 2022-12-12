<?php // lint >= 8.1

namespace Bug8485;

enum E {
	case c;
}

enum F {
	case c;
}

function shouldError():void {
	$e = E::c;
	$f = F::c;

	if ($e === E::c) {
	}
	if ($e == E::c) {
	}

	if ($f === $e) {
	}
	if ($f == $e) {
	}

	if ($f === E::c) {
	}
	if ($f == E::c) {
	}
}


function allGood(E $e, F $f):void {
	if ($f === $e) {
	}
	if ($f == $e) {
	}

	if ($f === E::c) {
	}
	if ($f == E::c) {
	}
}


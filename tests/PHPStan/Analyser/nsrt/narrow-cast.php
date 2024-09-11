<?php

namespace NarrowBoolCast;

use function PHPStan\Testing\assertType;

/** @param array<mixed> $arr */
function doFoo(string $x, array $arr): void {
	if ((bool) strlen($x)) {
		assertType('string', $x); // could be non-empty-string
	} else {
		assertType('string', $x);
	}
	assertType('string', $x);

	if ((bool) array_search($x, $arr, true)) {
		assertType('non-empty-array', $arr);
	} else {
		assertType('array', $arr);
	}
	assertType('string', $x);

	if ((bool) preg_match('~.*~', $x, $matches)) {
		assertType('array{string}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{string}', $matches);
}

/** @param int<-5, 5> $x */
function castString($x, string $s, bool $b) {
	if ((string) $x) {
		assertType('int<-5, -1>|int<1, 5>', $x);
	} else {
		assertType('0', $x);
	}

	if ((string) $b) {
		assertType('true', $b);
	} else {
		assertType('false', $b);
	}

	if ((string) strrchr($s, 'xy')) {
		assertType('string', $s); // could be non-empty-string
	} else {
		assertType('string', $s);
	}
}

/** @param int<-5, 5> $x */
function castInt($x, string $s, bool $b) {
    if ((int) $x) {
        assertType('int<-5, -1>|int<1, 5>', $x);
    } else {
        assertType('0', $x);
    }

    if ((int) $b) {
        assertType('true', $b);
    } else {
        assertType('false', $b);
    }

	if ((int) $s) {
		assertType('string', $s);
	} else {
		assertType('string', $s);
	}

    if ((int) strpos($s, 'xy')) {
        assertType('non-falsy-string', $s);
    } else {
        assertType('string', $s);
    }
}

/** @param int<-5, 5> $x */
function castFloat($x, string $s, bool $b) {
    if ((float) $x) {
        assertType('int<-5, -1>|int<1, 5>', $x);
    } else {
        assertType('0', $x);
    }

    if ((float) $b) {
        assertType('true', $b);
    } else {
        assertType('false', $b);
    }

    if ((float) $s) {
        assertType('string', $s);
    } else {
        assertType("string", $s);
    }
}

<?php

namespace Bug4700;

use function PHPStan\Testing\assertType;

function(array $array, int $count): void {
	if ($count < 1) {
		return;
	}

	assertType('int<1, max>', $count);

	$a = [];
	if (isset($array['a'])) $a[] = $array['a'];
	if (isset($array['b'])) $a[] = $array['b'];
	if (isset($array['c'])) $a[] = $array['c'];
	if (isset($array['d'])) $a[] = $array['d'];
	if (isset($array['e'])) $a[] = $array['e'];
	if (count($a) >= $count) {
		assertType('int<1, 5>', count($a));
		assertType('array{0: mixed~null, 1?: mixed~null, 2?: mixed~null, 3?: mixed~null, 4?: mixed~null}', $a);
	} else {
		assertType('int<0, 5>', count($a));
		assertType('array{}|array{0: mixed~null, 1?: mixed~null, 2?: mixed~null, 3?: mixed~null, 4?: mixed~null}', $a);
	}
};

function(array $array, int $count): void {
	if ($count < 1) {
		return;
	}

	assertType('int<1, max>', $count);

	$a = [];
	if (isset($array['a'])) $a[] = $array['a'];
	if (isset($array['b'])) $a[] = $array['b'];
	if (isset($array['c'])) $a[] = $array['c'];
	if (isset($array['d'])) $a[] = $array['d'];
	if (isset($array['e'])) $a[] = $array['e'];
	if (count($a) > $count) {
		assertType('int<2, 5>', count($a));
		assertType('array{0: mixed~null, 1?: mixed~null, 2?: mixed~null, 3?: mixed~null, 4?: mixed~null}', $a);
	} else {
		assertType('int<0, 5>', count($a));
		assertType('array{}|array{0: mixed~null, 1?: mixed~null, 2?: mixed~null, 3?: mixed~null, 4?: mixed~null}', $a);
	}
};

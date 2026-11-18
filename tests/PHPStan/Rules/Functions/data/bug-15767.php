<?php declare(strict_types = 1);

namespace Bug15767ReturnType;

/** @return finite-float */
function int_to_float(int $x): float {
	return (float) $x;
}

/**
 * @param decimal-int-string $x
 * @return finite-float
 */
function decimal_int_string_to_float(string $x): float {
	return (float) $x;
}

/** @return finite-float */
function int_plus_float(int $x): float {
	return $x + 0.0;
}

/** @return finite-float */
function int_settype(int $x): float {
	settype($x, 'float');
	return $x;
}

/**
 * @param non-decimal-int-string $x
 * @return finite-float
 */
function non_decimal_int_string_to_float(string $x): float {
	return (float) $x;
}

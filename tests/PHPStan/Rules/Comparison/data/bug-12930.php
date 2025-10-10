<?php declare(strict_types = 1);

namespace Bug12930;

/** @return mixed */
function getMixed() {return \stdClass::class;}

$val = getMixed();
if (is_string($val) && !is_a($val, \stdClass::class, true)) {
	throw new \Exception();
}

echo is_string($val) ? 'string' : 'something else';

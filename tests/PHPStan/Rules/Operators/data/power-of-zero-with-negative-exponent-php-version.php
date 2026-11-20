<?php

namespace PowerOfZeroWithNegativeExponentPhpVersion;

if (PHP_VERSION_ID < 80400) {
	var_dump(0 ** -1);
}

if (PHP_VERSION_ID >= 80400) {
	var_dump(0 ** -1);
}

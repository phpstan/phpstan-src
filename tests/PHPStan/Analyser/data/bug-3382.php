<?php

namespace Bug3382;

use function PHPStan\Analyser\assertType;

if (ini_get('auto_prepend_file')) {
	assertType('string', ini_get('auto_prepend_file'));
}

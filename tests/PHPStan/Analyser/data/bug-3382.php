<?php

namespace Bug3382;

use function PHPStan\Testing\assertType;

if (ini_get('auto_prepend_file')) {
	assertType('non-empty-string', ini_get('auto_prepend_file'));
}

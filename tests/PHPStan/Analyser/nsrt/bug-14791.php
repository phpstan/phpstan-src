<?php

namespace Bug14791;

use function PHPStan\Testing\assertType;

$name = 'c054-foo';

if (preg_match('/^c(0[0-9]{2})-foo/', $name, $matches)) {
	assertType('non-falsy-string&numeric-string', $matches[1]); // should not be decimal-int-string
	if ($matches[1] === '054') { // should not error
		// ...
	}
}

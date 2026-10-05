<?php

namespace SscanfPHP80;

use function PHPStan\Testing\assertType;

function sscanfInvalidFormatMixingPositionalWithSequential(string $s) {
	assertType('*NEVER*', sscanf($s, '%1$s %s'));
}

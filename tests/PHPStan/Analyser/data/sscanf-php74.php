<?php

namespace SscanfPHP74;

use function PHPStan\Testing\assertType;

function sscanfInvalidFormatMixingPositionalWithSequential(string $s) {
	assertType('null', sscanf($s, '%1$s %s'));
}

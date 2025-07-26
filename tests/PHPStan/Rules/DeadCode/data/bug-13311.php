<?php

namespace Bug13311;

use Exception;

$error_handler = static function () { throw new Exception(); };
set_error_handler($error_handler, \E_NOTICE | \E_WARNING);

try {
	$out = iconv($from, $to . $iconv_options, $str);
} catch (Throwable $e) {
	$out = false;
}

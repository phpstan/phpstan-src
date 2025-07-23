<?php // lint >= 8.1

namespace Bug13288;

function error_to_exception(int $errno, string $errstr, string $errfile = 'unknown', int $errline = 0): never {
	throw new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
}

set_error_handler(error_to_exception(...));

echo 'ok';

<?php declare(strict_types=1);

$path = __DIR__ . '/include-me-to-prove-you-work.txt';

if (rand(0,1)) {
	$path = 'a-file-that-does-not-exist.php';
}

include $path;
include_once $path;
require $path;
require_once $path;

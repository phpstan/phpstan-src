<?php declare(strict_types=1);

$fileThatExists = __DIR__ . '/include-me-to-prove-you-work.txt';
$fileThatDoesNotExist = 'a-file-that-does-not-exist.php';

include $fileThatExists;
include_once $fileThatExists;
require $fileThatExists;
require_once $fileThatExists;

include $fileThatDoesNotExist;
include_once $fileThatDoesNotExist;
require $fileThatDoesNotExist;
require_once $fileThatDoesNotExist;

$fileThatExists = __DIR__ . \DIRECTORY_SEPARATOR . 'include-me-to-prove-you-work.txt';

<?php declare(strict_types=1);

$includedFile = __DIR__ . 'include-me-to-prove-you-work.txt';

include $includedFile;
include_once $includedFile;
require $includedFile;
require_once $includedFile;

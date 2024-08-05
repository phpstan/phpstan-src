<?php declare(strict_types=1);

$includedFile = 'a-file-that-does-not-exist.php';

include $includedFile;
include_once $includedFile;
require $includedFile;
require_once $includedFile;

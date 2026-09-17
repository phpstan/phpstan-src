<?php declare(strict_types = 1);

namespace RequireFileIniSetUnknown;

ini_set($_SERVER['OPTION'], $_SERVER['VALUE']);

require_once 'a-file-that-does-not-exist.php';
require_once __DIR__ . '/a-file-that-does-not-exist.php';

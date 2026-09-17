<?php declare(strict_types = 1);

namespace RequireFileIniSetUnknown;

require_once 'a-file-that-does-not-exist.php';

ini_set($_SERVER['OPTION'], $_SERVER['VALUE']);

require_once 'a-file-that-does-not-exist.php';

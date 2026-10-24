<?php declare(strict_types = 1);

namespace RequireFileIncludePath;

ini_set('memory_limit', '1G');
ini_alter('precision', '10');

require_once 'a-file-that-does-not-exist.php';

ini_set('INCLUDE_PATH', __DIR__);

require_once 'a-file-that-does-not-exist.php';

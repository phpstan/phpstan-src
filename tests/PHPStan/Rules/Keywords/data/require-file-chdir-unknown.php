<?php declare(strict_types = 1);

namespace RequireFileChdirUnknown;

require_once 'a-file-that-does-not-exist.php';

chdir($_SERVER['CHDIR_TARGET']);

require_once 'a-file-that-does-not-exist.php';
require_once __DIR__ . '/a-file-that-does-not-exist.php';

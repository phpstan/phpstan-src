<?php declare(strict_types = 1);

namespace RequireFileIncludePathUnknown;

require_once 'a-file-that-does-not-exist.php';

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);

require_once 'a-file-that-does-not-exist.php';
require_once __DIR__ . '/a-file-that-does-not-exist.php';

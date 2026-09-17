<?php declare(strict_types = 1);

namespace RequireFileIncludePath;

set_include_path(__DIR__ . '/bug-15260');
require_once 'config.php';
require_once 'a-file-that-does-not-exist.php';

ini_set('memory_limit', '1G');
require_once 'a-file-that-does-not-exist.php';

ini_set('include_path', __DIR__ . '/bug-15260/sub');
require_once 'bug-15260.php';

ini_alter('INCLUDE_PATH', __DIR__ . '/bug-11738');
require_once 'bug-11738.php';

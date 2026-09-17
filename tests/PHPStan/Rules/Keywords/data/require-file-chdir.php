<?php declare(strict_types = 1);

namespace RequireFileChdir;

require_once 'a-file-that-does-not-exist.php';

chdir('..');

require_once 'a-file-that-does-not-exist.php';
require_once __DIR__ . '/a-file-that-does-not-exist.php';

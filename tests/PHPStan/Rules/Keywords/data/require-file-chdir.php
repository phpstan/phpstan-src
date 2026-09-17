<?php declare(strict_types = 1);

namespace RequireFileChdir;

chdir('Keywords');
require_once 'data/include-me-to-prove-you-work.txt';
require_once 'data/a-file-that-does-not-exist.php';
require_once __DIR__ . '/a-file-that-does-not-exist.php';

\chdir('data/bug-15260');
require_once 'config.php';

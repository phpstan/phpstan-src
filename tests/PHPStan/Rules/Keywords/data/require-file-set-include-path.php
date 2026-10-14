<?php declare(strict_types = 1);

namespace RequireFileSetIncludePath;

require_once 'a-file-that-does-not-exist.php';

\set_include_path(__DIR__);

require_once 'a-file-that-does-not-exist.php';

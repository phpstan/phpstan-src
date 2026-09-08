<?php declare(strict_types = 1);

namespace RequireFileStreamWrapper;

// vfsStream registers the "vfs" wrapper from a test's setUp(), so it is not registered while
// PHPStan runs and is_file() on the path cannot answer - it raises a warning instead.
include_once 'vfs://drupal/sites/default/modules/module_a/module_a.post_update.php';
require 'not-a-registered-wrapper://somewhere/else.php';

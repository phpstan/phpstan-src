<?php // lint >= 8.0

namespace Bug6258;

defined('a') || die();
defined('a') or die();
rand() === rand() || die();


defined('a') || exit();
defined('a') or exit();
rand() === rand() || exit();


defined('a') || throw new \Exception('');
defined('a') or  throw new \Exception('');
rand() === rand() ||  throw new \Exception('');

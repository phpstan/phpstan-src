<?php

namespace fooBug6348;

class bar {}

namespace abcBug6348;

require_once __DIR__ . '/empty.php';

use fooBug6348\bar;

/** @var bar $x */
$x = new bar();

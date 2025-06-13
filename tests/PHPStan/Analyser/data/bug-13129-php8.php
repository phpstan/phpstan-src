<?php

namespace Bug13129PHP8;

use function PHPStan\Testing\assertType;

assertType("''", substr("test", 5));
assertType("''", substr("test", -1, -5));
assertType("''", substr("test", 1, -4));

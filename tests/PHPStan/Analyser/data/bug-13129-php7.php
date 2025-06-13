<?php

namespace Bug13129PHP7;

use function PHPStan\Testing\assertType;

assertType("false", substr("test", 5));
assertType("false", substr("test", -1, -5));
assertType("false", substr("test", 1, -4));

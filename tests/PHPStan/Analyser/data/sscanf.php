<?php

use function PHPStan\Testing\assertType;

assertType('int|null', sscanf('20-20', '%d-%d', $first, $second));
assertType('array|null', sscanf('20-20', '%d-%d'));

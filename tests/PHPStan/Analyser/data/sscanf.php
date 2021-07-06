<?php

use function PHPStan\Testing\assertType;

assertType('int|null', sccanf('20-20', '%d-%d', $first, $second));
assertType('array|null', sccanf('20-20', '%d-%d'));

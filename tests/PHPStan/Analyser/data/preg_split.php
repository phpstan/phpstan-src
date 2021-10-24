<?php

use function PHPStan\Testing\assertType;

assertType('array<int, string>|false', preg_split('/-/', '1-2-3'));
assertType('array<int, string>|false', preg_split('/-/', '1-2-3', -1, PREG_SPLIT_NO_EMPTY));
assertType('array<int, array{string, int}>|false', preg_split('/-/', '1-2-3', -1, PREG_SPLIT_OFFSET_CAPTURE));
assertType('array<int, array{string, int}>|false', preg_split('/-/', '1-2-3', -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE));

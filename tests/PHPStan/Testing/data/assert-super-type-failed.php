<?php

use function PHPStan\Testing\assertSuperType;

$a = 'Alice';

assertSuperType('never', $a);
assertSuperType('bool', $a);
assertSuperType('"Bob"', $a);

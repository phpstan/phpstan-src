<?php

use function PHPStan\Testing\assertSuperType;

$a = 'Alice';

assertSuperType('string', $a);
assertSuperType('mixed', $a);

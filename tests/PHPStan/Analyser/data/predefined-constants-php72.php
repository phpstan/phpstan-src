<?php

use function PHPStan\Testing\assertType;

// core, https://www.php.net/manual/en/reserved.constants.php
assertType('\'BSD\'|\'Darwin\'|\'Linux\'|\'Solaris\'|\'Unknown\'|\'Windows\'', PHP_OS_FAMILY);
assertType('int<1, max>', PHP_FLOAT_DIG);
assertType('float', PHP_FLOAT_EPSILON);
assertType('float', PHP_FLOAT_MIN);
assertType('float', PHP_FLOAT_MAX);
assertType('int<1, max>', PHP_FD_SETSIZE);

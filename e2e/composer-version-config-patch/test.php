<?php

// constraint from composer.json is overruled by phpstan.neon config
\PHPStan\Testing\assertType('int<80001, 80015>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('8', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('0', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<1, 15>', PHP_RELEASE_VERSION);

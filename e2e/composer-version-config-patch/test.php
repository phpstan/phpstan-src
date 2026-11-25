<?php

// composer.json requires ">=8.0.2, <8.0.15", so PHP 8.0.2 - PHP 8.0.14
\PHPStan\Testing\assertType('int<80002, 80014>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('8', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('0', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<2, 14>', PHP_RELEASE_VERSION);

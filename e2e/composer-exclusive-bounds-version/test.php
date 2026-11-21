<?php

// composer.json requires ">8.2.3 <8.5.2", so PHP 8.2.4 - PHP 8.5.1
\PHPStan\Testing\assertType('int<80204, 80501>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('8', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('int<2, 5>', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_RELEASE_VERSION);

<?php

\PHPStan\Testing\assertType('int<50600, 50699>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('5', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('6', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<0, 99>', PHP_RELEASE_VERSION);

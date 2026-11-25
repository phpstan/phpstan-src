<?php

// composer.json requires "8.4.*", which excludes PHP 8.5.0
\PHPStan\Testing\assertType('int<80400, 80499>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('8', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('4', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<0, 99>', PHP_RELEASE_VERSION);

\PHPStan\Testing\assertType('false', version_compare(PHP_VERSION, '8.5.0', '>='));

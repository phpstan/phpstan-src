<?php

\PHPStan\Testing\assertType('int<80100, 80599>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('8', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('int<1, 5>', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_RELEASE_VERSION);

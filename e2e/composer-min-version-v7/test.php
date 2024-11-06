<?php

\PHPStan\Testing\assertType('int<70000, 70499>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('7', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('int<0, 4>', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_RELEASE_VERSION);

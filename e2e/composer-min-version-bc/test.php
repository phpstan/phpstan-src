<?php

// no bleeding edge enabled
\PHPStan\Testing\assertType('int<50207, max>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('int<5, max>', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_RELEASE_VERSION);

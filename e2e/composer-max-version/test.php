<?php

\PHPStan\Testing\assertType('int<50207, 80300>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('int<5, 8>', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_RELEASE_VERSION);

\PHPStan\Testing\assertType('-1|0|1', version_compare(PHP_VERSION, '7.0.0'));
\PHPStan\Testing\assertType('bool', version_compare(PHP_VERSION, '7.0.0', '<'));
\PHPStan\Testing\assertType('bool', version_compare(PHP_VERSION, '7.0.0', '>'));

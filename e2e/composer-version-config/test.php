<?php

// constraint from composer.json is overruled by phpstan.neon config
\PHPStan\Testing\assertType('int<80103, 80304>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('8', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('int<1, 3>', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_RELEASE_VERSION);

\PHPStan\Testing\assertType('1', version_compare(PHP_VERSION, '7.0.0'));
\PHPStan\Testing\assertType('false', version_compare(PHP_VERSION, '7.0.0', '<'));
\PHPStan\Testing\assertType('true', version_compare(PHP_VERSION, '7.0.0', '>'));

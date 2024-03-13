<?php

\PHPStan\Testing\assertType('int<80099, max>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('int<8, max>', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_RELEASE_VERSION);

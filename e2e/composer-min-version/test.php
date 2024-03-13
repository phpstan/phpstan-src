<?php

\PHPStan\Testing\assertType('int<80099, 90000>', PHP_VERSION_ID);
\PHPStan\Testing\assertType('int<8, 9>', PHP_MAJOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_MINOR_VERSION);
\PHPStan\Testing\assertType('int<0, max>', PHP_RELEASE_VERSION);

<?php

\PHPStan\Testing\assertType('array<int|string, mixed>', $GLOBALS);
\PHPStan\Testing\assertType('array<int|string, mixed>', $_SERVER);
\PHPStan\Testing\assertType('array<int|string, mixed>', $_GET);
\PHPStan\Testing\assertType('array<int|string, mixed>', $_POST);
\PHPStan\Testing\assertType('array<int|string, mixed>', $_FILES);
\PHPStan\Testing\assertType('array<int|string, mixed>', $_COOKIE);
\PHPStan\Testing\assertType('array<int|string, mixed>', $_SESSION);
\PHPStan\Testing\assertType('array<int|string, mixed>', $_REQUEST);
\PHPStan\Testing\assertType('array<int|string, mixed>', $_ENV);

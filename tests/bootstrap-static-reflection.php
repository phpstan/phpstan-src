<?php declare(strict_types = 1);

require_once __DIR__ . '/bootstrap.php';

\PHPStan\Testing\PHPStanTestCase::$useStaticReflectionProvider = true;

\PHPStan\Testing\PHPStanTestCase::getContainer();

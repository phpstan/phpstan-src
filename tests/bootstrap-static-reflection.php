<?php declare(strict_types = 1);

require_once __DIR__ . '/bootstrap.php';

\PHPStan\Testing\BaseTestCase::$useStaticReflectionProvider = true;

\PHPStan\Testing\BaseTestCase::getContainer();

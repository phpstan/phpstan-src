<?php declare(strict_types = 1);

use PHPStan\Testing\PHPStanTestCase;

require_once __DIR__ . '/bootstrap.php';

class_exists('EnumTypeAssertions\\Foo');

PHPStanTestCase::$useStaticReflectionProvider = false;

PHPStanTestCase::getContainer();

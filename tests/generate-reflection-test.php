<?php declare(strict_types = 1);

namespace PHPStan;

use PHPStan\Reflection\ReflectionProviderGoldenTest;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/PHPStan/Reflection/ReflectionProviderGoldenTest.php';

ReflectionProviderGoldenTest::dumpOutput();

<?php declare(strict_types = 1);

// What a Symfony application's entry script does in dev: DebugClassLoader wraps every
// registered autoloader and throws a RuntimeException when the file it found does not
// declare the class it was asked for, or when the case does not match.
require_once __DIR__ . '/vendor/autoload.php';

Symfony\Component\ErrorHandler\DebugClassLoader::enable();

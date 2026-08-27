<?php declare(strict_types = 1);

require_once __DIR__ . '/E2eNestedClassLoader.php';

$loader = new E2eNestedClassLoader();
$loader->register();

return $loader;

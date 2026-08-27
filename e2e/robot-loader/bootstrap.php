<?php declare(strict_types = 1);

require __DIR__ . '/vendor/autoload.php';

$loader = new Nette\Loaders\RobotLoader();
$loader->addDirectory(__DIR__ . '/classes');
$loader->setTempDirectory(sys_get_temp_dir() . '/phpstan-e2e-robot-loader');
$loader->register();

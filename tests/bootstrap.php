<?php declare(strict_types = 1);

use PHPStan\DependencyInjection\ContainerFactory;

error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/PHPStan/Rules/AlwaysFailRule.php';
require_once __DIR__ . '/PHPStan/Rules/DummyRule.php';
require_once __DIR__ . '/phpstan-bootstrap.php';
require_once __DIR__ . '/PHPStan/Analyser/functions.php';

eval('trait TraitInEval {

	/**
	 * @param int $i
	 */
	public function doFoo($i)
	{
	}

}');

$tmpDir = sys_get_temp_dir() . '/phpstan-tests';
if (!@mkdir($tmpDir, 0777, true) && !is_dir($tmpDir)) {
	echo sprintf('Cannot create temp directory %s', $tmpDir) . "\n";
	exit(1);
}

// to register the right Broker as first
$rootDir = __DIR__ . '/..';
$containerFactory = new ContainerFactory($rootDir);
$containerFactory->create($tmpDir, [
	$containerFactory->getConfigDirectory() . '/config.level8.neon',
], []);

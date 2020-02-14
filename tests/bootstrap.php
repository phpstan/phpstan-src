<?php declare(strict_types = 1);

use PHPStan\Cache\FileCacheStorage;
use PHPStan\Testing\TestCase;

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

$container = TestCase::getContainer();
$fileCacheStorage = $container->getService('cacheStorage');
if ($fileCacheStorage instanceof FileCacheStorage) {
	$fileCacheStorage->makeRootDir();
}

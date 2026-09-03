<?php declare(strict_types = 1);

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Turbo\TurboExtensionEnabler;

error_reporting(E_ALL);

require_once __DIR__ . '/../src/Turbo/TurboExtensionEnabler.php';
TurboExtensionEnabler::enableIfLoaded(); // @phpstan-ignore phpstanApi.method

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/PHPStan/Rules/AlwaysFailRule.php';
require_once __DIR__ . '/PHPStan/Rules/DummyRule.php';
require_once __DIR__ . '/phpstan-bootstrap.php';

putenv('PHPSTAN_ALLOW_XDEBUG=1');

eval('trait TraitInEval {

	/**
	 * @param int $i
	 */
	public function doFoo($i)
	{
	}

}');

// A ParaTest worker builds the test suite of the first test file it picks up - and with it
// runs that file's data providers - before any PHPUnit hook of ours gets a chance to run on
// PHPUnit 9, which rejects PHPStanPHPUnitExtension. Install the base container's global state
// (the static reflection provider, the PhpVersion, the bleeding edge toggle) up front so those
// data providers see the same state as every later one.
PHPStanTestCase::restoreBaseContainer();

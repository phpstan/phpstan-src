<?php declare(strict_types = 1);

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Event\Facade;
use PHPUnit\Event\Test\DataProviderMethodCalled;
use PHPUnit\Event\Test\DataProviderMethodCalledSubscriber;

error_reporting(E_ALL);

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

class InitContainerBeforeDataProvider implements DataProviderMethodCalledSubscriber
{

	public function notify(DataProviderMethodCalled $event): void
	{
		PHPStanTestCase::getContainer();
	}

}


Facade::instance()->registerSubscriber(new InitContainerBeforeDataProvider());

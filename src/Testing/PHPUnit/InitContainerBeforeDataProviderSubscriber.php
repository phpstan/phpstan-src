<?php declare(strict_types = 1);

namespace PHPStan\Testing\PHPUnit;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Event\Test\DataProviderMethodCalled;
use PHPUnit\Event\Test\DataProviderMethodCalledSubscriber;
use function is_a;

class InitContainerBeforeDataProviderSubscriber implements DataProviderMethodCalledSubscriber
{

	public function notify(DataProviderMethodCalled $event): void
	{
		$testClassName = $event->testMethod()->className();

		if (!is_a($testClassName, PhpStanTestCase::class, true)) {
			return;
		}

		$testClassName::getContainer();
	}

}

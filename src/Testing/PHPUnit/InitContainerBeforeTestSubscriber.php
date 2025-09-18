<?php declare(strict_types = 1);

namespace PHPStan\Testing\PHPUnit;

use Override;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use function is_a;

final class InitContainerBeforeTestSubscriber implements PreparationStartedSubscriber
{

	#[Override]
	public function notify(PreparationStarted $event): void
	{
		$test = $event->test();
		if (!$test->isTestMethod()) {
			// skip PHPT tests
			return;
		}

		$testClassName = $test->className();

		if (!is_a($testClassName, PhpStanTestCase::class, true)) {
			return;
		}

		ContainerInitializer::initialize($testClassName);
	}

}

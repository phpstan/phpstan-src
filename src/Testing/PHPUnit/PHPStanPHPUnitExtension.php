<?php declare(strict_types = 1);

namespace PHPStan\Testing\PHPUnit;

use Override;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class PHPStanPHPUnitExtension implements Extension
{

	#[Override]
	public function bootstrap(
		Configuration $configuration,
		Facade $facade,
		ParameterCollection $parameters,
	): void
	{
		// use multiple subscribers which all initialize the test-container.
		// we need to make sure we only initialize once per test-class.
		$facade->registerSubscriber(
			new InitContainerBeforeDataProviderSubscriber(),
		);
		$facade->registerSubscriber(
			new InitContainerBeforeTestSubscriber(),
		);
	}

}

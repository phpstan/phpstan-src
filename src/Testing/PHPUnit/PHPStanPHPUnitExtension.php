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
		$facade->registerSubscriber(
			new InitContainerBeforeDataProviderSubscriber(),
		);
	}

}

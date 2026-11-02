<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Arguments;

use PHPStan\DependencyInjection\AutowiredParameter;

final class DifferentParameter
{

	public function __construct(
		#[AutowiredParameter]
		private bool $checkUninitializedProperties,
	)
	{
	}

}

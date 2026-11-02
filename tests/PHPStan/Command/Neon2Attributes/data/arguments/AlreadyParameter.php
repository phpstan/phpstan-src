<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Arguments;

use PHPStan\DependencyInjection\AutowiredParameter;

final class AlreadyParameter
{

	public function __construct(
		#[AutowiredParameter]
		private bool $checkUninitializedProperties,
		private OnlyNamed $named,
	)
	{
	}

}

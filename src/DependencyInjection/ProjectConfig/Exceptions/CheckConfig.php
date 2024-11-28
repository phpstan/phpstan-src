<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig\Exceptions;

use PHPStan\DependencyInjection\ProjectConfig\Arrayable;
use PHPStan\DependencyInjection\ProjectConfig\ArrayableTrait;
use PHPStan\DependencyInjection\ProjectConfig\Undefined;

final class CheckConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		private readonly bool|Undefined $missingCheckedExceptionInThrows = Undefined::Undefined,
		private readonly bool|Undefined $tooWideThrowType = Undefined::Undefined,
	)
	{
	}

}

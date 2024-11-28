<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

final class ExceptionsCheckConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		private readonly bool|Undefined $missingCheckedExceptionInThrows = Undefined::Undefined,
		private readonly bool|Undefined $tooWideThrowType = Undefined::Undefined,
	)
	{
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

final class PhpVersionConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		private readonly int|Undefined $min = Undefined::Undefined,
		private readonly int|Undefined $max = Undefined::Undefined,
	)
	{
	}

}

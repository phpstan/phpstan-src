<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

final class CacheConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		private readonly int|Undefined $nodesByStringCountMax = Undefined::Undefined,
	)
	{
	}

}

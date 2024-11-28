<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

final class TipsConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		private readonly bool|Undefined $treatPhpDocTypesAsCertain = Undefined::Undefined,
	)
	{
	}

}

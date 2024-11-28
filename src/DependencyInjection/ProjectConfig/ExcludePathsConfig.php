<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

final class ExcludePathsConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		/** @var list<string>|Undefined */
		private readonly array|Undefined $analyse = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $analyseAndScan = Undefined::Undefined,
	)
	{
	}

}

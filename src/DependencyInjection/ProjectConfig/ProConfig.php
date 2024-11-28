<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

final class ProConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		/** @var list<string>  */
		private readonly array|Undefined $dnsServers = Undefined::Undefined,
		private readonly string|Undefined $tmpDir = Undefined::Undefined,
	)
	{
	}

}

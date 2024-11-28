<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

final class IgnoreErrorsConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		private readonly string|Undefined $message = Undefined::Undefined,
		/** @var list<string> */
		private readonly array|Undefined $messages = Undefined::Undefined,
		private readonly int|Undefined $count = Undefined::Undefined,
		private readonly string|Undefined $path = Undefined::Undefined,
		/** @var list<string> */
		private readonly array|Undefined $paths = Undefined::Undefined,
		private readonly string|Undefined $identifier = Undefined::Undefined,
		private readonly bool|Undefined $reportUnmatched = Undefined::Undefined,
	)
	{
	}

}

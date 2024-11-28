<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

final class ExceptionsConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		private readonly bool|Undefined $implicitThrows = Undefined::Undefined,
		private readonly bool|Undefined $reportUncheckedExceptionDeadCatch = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $uncheckedExceptionRegexes = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $uncheckedExceptionClasses = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $checkedExceptionRegexes = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $checkedExceptionClasses = Undefined::Undefined,
		private readonly ExceptionsCheckConfig|Undefined $check = Undefined::Undefined,
	)
	{
	}

}

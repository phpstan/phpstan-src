<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

use PhpParser\Node;
use PHPStan\Rules\Rule;

final class ProjectConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		/** @var list<string> */
		private readonly array|Undefined $includes = Undefined::Undefined,
		private readonly ParametersConfig|Undefined $parameters = Undefined::Undefined,
		/** @var list<class-string<Rule<Node>>> */
		private readonly array|Undefined $rules = Undefined::Undefined,
		/** @var array<array-key, mixed> */
		// phpcs:ignore Consistence.NamingConventions.ValidVariableName.NotCamelCaps
		private readonly array|Undefined $_extra = Undefined::Undefined,
	)
	{
	}

}

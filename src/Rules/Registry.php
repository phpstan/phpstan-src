<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;

interface Registry
{

	/**
	 * @template TNodeType of Node
	 * @phpstan-param class-string<TNodeType> $nodeType
	 * @param Node $nodeType
	 * @phpstan-return array<Rule<TNodeType>>
	 * @return Rule[]
	 */
	public function getRules(string $nodeType): array;

}

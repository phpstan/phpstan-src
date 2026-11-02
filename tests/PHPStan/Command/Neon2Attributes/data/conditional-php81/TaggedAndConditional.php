<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\ConditionalPhp81;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Expr\New_>
 */
final class TaggedAndConditional implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\New_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [];
	}

}

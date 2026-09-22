<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @implements MultipleNodeTypesRule<Node\Expr>
 */
class DummyMultipleNodeTypesRule implements MultipleNodeTypesRule
{

	/**
	 * @param non-empty-list<class-string<Node\Expr>> $nodeTypes
	 */
	public function __construct(private array $nodeTypes)
	{
	}

	public function getNodeTypes(): array
	{
		return $this->nodeTypes;
	}

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [];
	}

}

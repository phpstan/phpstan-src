<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementResult;

/**
 * @api
 * @final
 */
class ExecutionEndNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private Node\Stmt $node,
		private StatementResult $statementResult,
		private bool $hasNativeReturnTypehint,
	)
	{
		parent::__construct($node->getAttributes());
	}

	public function getNode(): Node\Stmt
	{
		return $this->node;
	}

	public function getStatementResult(): StatementResult
	{
		return $this->statementResult;
	}

	public function hasNativeReturnTypehint(): bool
	{
		return $this->hasNativeReturnTypehint;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_ExecutionEndNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}

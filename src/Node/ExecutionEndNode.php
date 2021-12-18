<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementResult;

/** @api */
class ExecutionEndNode extends NodeAbstract implements VirtualNode
{

	private Node $node;

	private StatementResult $statementResult;

	private bool $hasNativeReturnTypehint;

	public function __construct(
		Node $node,
		StatementResult $statementResult,
		bool $hasNativeReturnTypehint,
	)
	{
		parent::__construct($node->getAttributes());
		$this->node = $node;
		$this->statementResult = $statementResult;
		$this->hasNativeReturnTypehint = $hasNativeReturnTypehint;
	}

	public function getNode(): Node
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

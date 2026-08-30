<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\StatementResult;

/**
 * @api
 */
final class ExecutionEndNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private Node\Stmt $node,
		private StatementResult $statementResult,
		private bool $hasNativeReturnTypehint,
		private ?ExpressionResult $exprResult = null,
	)
	{
		parent::__construct($node->getAttributes());
	}

	public function getNode(): Node\Stmt
	{
		return $this->node;
	}

	/**
	 * The result of the expression the ending statement evaluated. Null when the
	 * statement is not an expression statement, for the synthetic statement
	 * wrapping a closure with an empty body, and when the expression was walked
	 * into a storage the end node is not built in.
	 *
	 * @internal
	 */
	public function getExprResult(): ?ExpressionResult
	{
		return $this->exprResult;
	}

	public function getStatementResult(): StatementResult
	{
		return $this->statementResult;
	}

	public function hasNativeReturnTypehint(): bool
	{
		return $this->hasNativeReturnTypehint;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_ExecutionEndNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}

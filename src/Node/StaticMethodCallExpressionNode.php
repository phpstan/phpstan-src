<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ExpressionResult;

/**
 * Emitted by NodeScopeResolver once a (non-first-class) static-method call has been
 * processed and stored, so impossible-check rules read the call's already-computed
 * specified types from the carried ExpressionResult instead of asking the scope to
 * specify them before the call node itself is processed.
 *
 * @internal
 */
final class StaticMethodCallExpressionNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private StaticCall $originalNode,
		private ExpressionResult $result,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getOriginalNode(): StaticCall
	{
		return $this->originalNode;
	}

	public function getResult(): ExpressionResult
	{
		return $this->result;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_StaticMethodCallExpressionNode';
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

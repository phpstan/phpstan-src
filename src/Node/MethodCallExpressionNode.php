<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ArgsResult;
use PHPStan\Analyser\ExpressionResult;

/**
 * Emitted by NodeScopeResolver once a (non-first-class) method call has been
 * processed and stored, so impossible-check rules read the call's already-computed
 * specified types from the carried ExpressionResult instead of asking the scope to
 * specify them before the call node itself is processed.
 *
 * @internal
 */
final class MethodCallExpressionNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private MethodCall $originalNode,
		private ExpressionResult $result,
		private ?ArgsResult $argsResult = null,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	/**
	 * The processed arguments of the call, so rules read an argument's type from
	 * its own result instead of asking the scope before the argument was stored.
	 * Null for a call whose arguments were not processed (first-class callable).
	 */
	public function getArgsResult(): ?ArgsResult
	{
		return $this->argsResult;
	}

	public function getOriginalNode(): MethodCall
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
		return 'PHPStan_Node_MethodCallExpressionNode';
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

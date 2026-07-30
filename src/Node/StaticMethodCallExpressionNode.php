<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\NodeAbstract;

/**
 * Emitted by NodeScopeResolver once the call has been processed and stored, so
 * rules listening on it (e.g. the impossible-check rules) run on the fully
 * processed call instead of asking the scope to specify its types before the
 * call node itself is processed.
 *
 * @internal
 */
final class StaticMethodCallExpressionNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private StaticCall $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getOriginalNode(): StaticCall
	{
		return $this->originalNode;
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

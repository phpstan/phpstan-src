<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\NodeAbstract;
use PHPStan\Type\Type;

/**
 * Emitted by NullsafeMethodCallHandler once the receiver has been processed, so
 * NullsafeMethodCallRule reads the receiver's (possibly null) type from the carried
 * type instead of asking the scope for the receiver type before it is processed.
 *
 * @internal
 */
final class NullsafeMethodCallExpressionNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private NullsafeMethodCall $originalNode,
		private Type $calledOnType,
		private Type $calledOnNativeType,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getOriginalNode(): NullsafeMethodCall
	{
		return $this->originalNode;
	}

	public function getCalledOnType(): Type
	{
		return $this->calledOnType;
	}

	public function getCalledOnNativeType(): Type
	{
		return $this->calledOnNativeType;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_NullsafeMethodCallExpressionNode';
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

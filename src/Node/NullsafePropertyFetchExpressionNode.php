<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\NodeAbstract;
use PHPStan\Type\Type;

/**
 * Emitted by NullsafePropertyFetchHandler once the receiver has been processed, so
 * NullsafePropertyFetchRule reads the receiver's (possibly null) type from the
 * carried type instead of asking the scope for the receiver type before it is
 * processed.
 *
 * @internal
 */
final class NullsafePropertyFetchExpressionNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private NullsafePropertyFetch $originalNode,
		private Type $calledOnType,
		private Type $calledOnNativeType,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getOriginalNode(): NullsafePropertyFetch
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
		return 'PHPStan_Node_NullsafePropertyFetchExpressionNode';
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

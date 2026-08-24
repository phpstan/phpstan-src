<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeAbstract;
use PHPStan\Type\Type;

/**
 * A catch clause inside a trait whose caught type _is_ thrown in the try block.
 *
 * Emitted only in traits. A trait's catch can be dead in the context of one class using
 * the trait and alive in the context of another, so CatchWithUnthrownExceptionRule has to
 * learn about the alive ones as well to notice the disagreement. Dead catches keep being
 * reported through CatchWithUnthrownExceptionNode.
 */
final class CatchWithThrownExceptionInTraitNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private Catch_ $originalNode, private Type $originalCaughtType)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getOriginalNode(): Catch_
	{
		return $this->originalNode;
	}

	public function getOriginalCaughtType(): Type
	{
		return $this->originalCaughtType;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_CatchWithThrownExceptionInTraitNode';
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

<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;

/**
 * Virtual node emitted once for each type in a `catch` clause, whether or not
 * the try block can throw it - unlike CatchWithUnthrownExceptionNode, which is
 * emitted only for the dead ones. Rules analysing a trait need both outcomes
 * to tell whether a catch is dead in every class using it.
 *
 * @api
 */
#[ReferencedByTurboExtension(key: 'catchTypeNode')]
final class CatchTypeNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private Catch_ $originalNode,
		private Name $typeNode,
		private Type $caughtType,
		private Type $originalCaughtType,
		private bool $thrown,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getOriginalNode(): Catch_
	{
		return $this->originalNode;
	}

	public function getTypeNode(): Name
	{
		return $this->typeNode;
	}

	/**
	 * The caught type without the types caught by the preceding catch clauses;
	 * NeverType when they already catch all of it.
	 */
	public function getCaughtType(): Type
	{
		return $this->caughtType;
	}

	public function getOriginalCaughtType(): Type
	{
		return $this->originalCaughtType;
	}

	public function isThrown(): bool
	{
		return $this->thrown;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_CatchTypeNode';
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

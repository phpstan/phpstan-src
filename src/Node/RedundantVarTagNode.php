<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt;
use PhpParser\NodeAbstract;
use PHPStan\PhpDoc\Tag\VarTag;

/**
 * An inline @var tag above the statement that changed nothing about what it
 * re-typed, emitted with reportRedundantVarTag once the statement is processed.
 */
final class RedundantVarTagNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private Stmt $stmt, private VarTag $varTag, private ?string $variableName)
	{
		parent::__construct($stmt->getAttributes());
	}

	public function getStmt(): Stmt
	{
		return $this->stmt;
	}

	public function getVarTag(): VarTag
	{
		return $this->varTag;
	}

	/** Null for a tag without a variable name. */
	public function getVariableName(): ?string
	{
		return $this->variableName;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_RedundantVarTag';
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

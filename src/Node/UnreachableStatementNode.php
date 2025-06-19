<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt;

/**
 * @api
 */
final class UnreachableStatementNode extends Stmt implements VirtualNode
{

	/** @param Stmt[] $nextStatements */
	public function __construct(private Stmt $originalStatement, private array $nextStatements = [])
	{
		parent::__construct($originalStatement->getAttributes());
	}

	public function getOriginalStatement(): Stmt
	{
		return $this->originalStatement;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Stmt_UnreachableStatementNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

	/**
	 * @return Stmt[]
	 */
	public function getNextStatements(): array
	{
		return $this->nextStatements;
	}

}

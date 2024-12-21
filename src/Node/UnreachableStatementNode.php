<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt;

/**
 * @api
 */
final class UnreachableStatementNode extends Stmt implements VirtualNode
{

	/** @var Stmt[] */
	private array $nextStatements = [];

	public function __construct(private Stmt $originalStatement)
	{
		parent::__construct($originalStatement->getAttributes());
	}

	public function getOriginalStatement(): Stmt
	{
		return $this->originalStatement;
	}

	public function getType(): string
	{
		return 'PHPStan_Stmt_UnreachableStatementNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

	public function addNextStatement(Stmt $stmt): void
	{
		$this->nextStatements[] = $stmt;
	}

	/**
	 * @return Stmt[]
	 */
	public function getNextStatements(): array
	{
		return $this->nextStatements;
	}

}

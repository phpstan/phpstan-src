<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PHPStan\Node\VirtualNode;
use function is_string;

final class IntertwinedVariableByReferenceWithExpr extends Expr implements VirtualNode
{

	public function __construct(private string $variableName, public Expr $expr, private Expr $assignedExpr)
	{
		parent::__construct([]);
	}

	public function getVariableName(): string
	{
		return $this->variableName;
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	public function getAssignedExpr(): Expr
	{
		return $this->assignedExpr;
	}

	public function isVariableToVariableReference(): bool
	{
		return $this->expr instanceof Variable
			&& is_string($this->expr->name)
			&& $this->assignedExpr instanceof Variable
			&& is_string($this->assignedExpr->name);
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_IntertwinedVariableByReferenceWithExpr';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return ['expr'];
	}

}

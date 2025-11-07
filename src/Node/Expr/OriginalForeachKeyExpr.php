<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;

final class OriginalForeachKeyExpr extends Expr implements VirtualNode
{

	public function __construct(private string $variableName)
	{
		parent::__construct([]);
	}

	public function getVariableName(): string
	{
		return $this->variableName;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_OriginalForeachKeyExpr';
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

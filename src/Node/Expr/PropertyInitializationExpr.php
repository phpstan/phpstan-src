<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;

final class PropertyInitializationExpr extends Expr implements VirtualNode
{

	public function __construct(private string $propertyName)
	{
		parent::__construct([]);
	}

	public function getPropertyName(): string
	{
		return $this->propertyName;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_PropertyInitializationExpr';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}

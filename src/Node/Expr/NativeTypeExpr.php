<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Type\Type;

/**
 * @api
 */
final class NativeTypeExpr extends Expr implements VirtualNode
{

	/** @api */
	public function __construct(private Type $phpdocType, private Type $nativeType)
	{
		parent::__construct();
	}

	public function getPhpDocType(): Type
	{
		return $this->phpdocType;
	}

	public function getNativeType(): Type
	{
		return $this->nativeType;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_NativeTypeExpr';
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

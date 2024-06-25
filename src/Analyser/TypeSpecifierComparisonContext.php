<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PHPStan\Type\Type;

/** @api */
final class TypeSpecifierComparisonContext
{

	public function __construct(
		private BinaryOp $binaryOp,
		private Expr $callExpr,
		private Type $comparisonType,
		private TypeSpecifierContext $context,
		private ?Expr $rootExpr,
	)
	{
	}

	public function getBinaryOp(): BinaryOp
	{
		return $this->binaryOp;
	}

	public function getCallExpr(): Expr
	{
		return $this->callExpr;
	}

	public function getComparisonType(): Type
	{
		return $this->comparisonType;
	}

	public function getTypeSpecifierContext(): TypeSpecifierContext
	{
		return $this->context;
	}

	public function getRootExpr(): ?Expr
	{
		return $this->rootExpr;
	}

}

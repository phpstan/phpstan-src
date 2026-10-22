<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\CompiledIntoTurboExtension;
use PHPStan\Type\Type;

#[CompiledIntoTurboExtension(new: ['pt_ensured_non_nullability_result_expression_new', 'val'])]
final class EnsuredNonNullabilityResultExpression
{

	public function __construct(
		private Expr $expression,
		private Type $originalType,
		private Type $originalNativeType,
		private TrinaryLogic $certainty,
	)
	{
	}

	public function getExpression(): Expr
	{
		return $this->expression;
	}

	public function getOriginalType(): Type
	{
		return $this->originalType;
	}

	public function getOriginalNativeType(): Type
	{
		return $this->originalNativeType;
	}

	public function getCertainty(): TrinaryLogic
	{
		return $this->certainty;
	}

}

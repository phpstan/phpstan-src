<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Type\Type;

class EnsuredNonNullabilityResultExpression
{

	private Expr $expression;

	private Type $originalType;

	private Type $originalNativeType;

	public function __construct(
		Expr $expression,
		Type $originalType,
		Type $originalNativeType
	)
	{
		$this->expression = $expression;
		$this->originalType = $originalType;
		$this->originalNativeType = $originalNativeType;
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

}

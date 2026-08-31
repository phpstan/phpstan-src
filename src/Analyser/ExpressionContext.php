<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Type;

final class ExpressionContext
{

	private function __construct(
		private bool $isDeep,
		private ?string $inAssignRightSideVariableName,
		private ?Expr $inAssignRightSideExpr,
		private bool $inThrow = false,
		private ?Type $inAssignRightSideType = null,
		private ?Type $inAssignRightSideNativeType = null,
	)
	{
	}

	public static function createTopLevel(): self
	{
		return new self(isDeep: false, inAssignRightSideVariableName: null, inAssignRightSideExpr: null);
	}

	public static function createDeep(): self
	{
		return new self(isDeep: true, inAssignRightSideVariableName: null, inAssignRightSideExpr: null);
	}

	public function enterDeep(): self
	{
		if ($this->isDeep) {
			return $this;
		}

		return new self(true, $this->inAssignRightSideVariableName, $this->inAssignRightSideExpr, $this->inThrow, $this->inAssignRightSideType, $this->inAssignRightSideNativeType);
	}

	public function isDeep(): bool
	{
		return $this->isDeep;
	}

	public function enterThrow(): self
	{
		return new self($this->isDeep, $this->inAssignRightSideVariableName, $this->inAssignRightSideExpr, true, $this->inAssignRightSideType, $this->inAssignRightSideNativeType);
	}

	public function isInThrow(): bool
	{
		return $this->inThrow;
	}

	public function enterRightSideAssign(string $variableName, Expr $expr): self
	{
		return new self($this->isDeep, $variableName, $expr, $this->inThrow);
	}

	public function getInAssignRightSideVariableName(): ?string
	{
		return $this->inAssignRightSideVariableName;
	}

	public function getInAssignRightSideExpr(): ?Expr
	{
		return $this->inAssignRightSideExpr;
	}

	/**
	 * The call that is the assignment's right side is about to process its
	 * arguments. A closure argument's by-ref use of the variable being
	 * assigned reads the call's type from inside those very arguments - a
	 * forward reference the acceptor's declared return type answers without
	 * pricing the enclosing call (template types resolve to their bounds).
	 */
	public function enterAssignRightSideCallArgs(ParametersAcceptor $acceptor): self
	{
		return new self(
			$this->isDeep,
			$this->inAssignRightSideVariableName,
			$this->inAssignRightSideExpr,
			$this->inThrow,
			TemplateTypeHelper::resolveToBounds($acceptor->getReturnType()),
			TemplateTypeHelper::resolveToBounds($acceptor instanceof ExtendedParametersAcceptor ? $acceptor->getNativeReturnType() : $acceptor->getReturnType()),
		);
	}

	public function getInAssignRightSideType(): ?Type
	{
		return $this->inAssignRightSideType;
	}

	public function getInAssignRightSideNativeType(): ?Type
	{
		return $this->inAssignRightSideNativeType;
	}

}

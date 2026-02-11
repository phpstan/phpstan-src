<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;

final class ExpressionContext
{

	private function __construct(
		private bool $isDeep,
		private ?string $inAssignRightSideVariableName,
		private ?Expr $inAssignRightSideExpr,
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

		return new self(true, $this->inAssignRightSideVariableName, $this->inAssignRightSideExpr);
	}

	public function isDeep(): bool
	{
		return $this->isDeep;
	}

	public function enterRightSideAssign(string $variableName, Expr $expr): self
	{
		return new self($this->isDeep, $variableName, $expr);
	}

	public function getInAssignRightSideVariableName(): ?string
	{
		return $this->inAssignRightSideVariableName;
	}

	public function getInAssignRightSideExpr(): ?Expr
	{
		return $this->inAssignRightSideExpr;
	}

}

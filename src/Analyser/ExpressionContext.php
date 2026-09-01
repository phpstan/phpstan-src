<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Node\Variable\VariableWrite;
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
		private ?VariableWrite $valueFlowTarget = null,
		private bool $valueFlowDirect = false,
		private bool $arrayDimFetchRoot = false,
		private bool $unsetTarget = false,
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

	/**
	 * The context of a sub-expression whose value does not flow into the
	 * enclosing expression's value (a call argument, a condition, a receiver):
	 * a value-flow target and the read flavours are dropped.
	 */
	public function enterDeep(): self
	{
		if ($this->isDeep && $this->valueFlowTarget === null && !$this->arrayDimFetchRoot && !$this->unsetTarget) {
			return $this;
		}

		return new self(true, $this->inAssignRightSideVariableName, $this->inAssignRightSideExpr, $this->inThrow, $this->inAssignRightSideType, $this->inAssignRightSideNativeType);
	}

	/**
	 * The context of an operand of a pure combinator (arithmetic, concat, a
	 * cast, a literal array item...): its value flows into the enclosing
	 * expression's value, so the value-flow target is kept.
	 */
	public function enterDeepKeepingValueFlow(): self
	{
		if ($this->valueFlowTarget === null) {
			return $this->enterDeep();
		}

		return new self(true, $this->inAssignRightSideVariableName, $this->inAssignRightSideExpr, $this->inThrow, $this->inAssignRightSideType, $this->inAssignRightSideNativeType, $this->valueFlowTarget, false);
	}

	/**
	 * The context of a sub-expression at the same depth whose value does not
	 * flow into the enclosing expression's value (the right operand of && / ||,
	 * a piped call, a closure use, the receiver of an assignment target).
	 */
	public function withoutValueFlow(): self
	{
		if ($this->valueFlowTarget === null && !$this->arrayDimFetchRoot && !$this->unsetTarget) {
			return $this;
		}

		return new self($this->isDeep, $this->inAssignRightSideVariableName, $this->inAssignRightSideExpr, $this->inThrow, $this->inAssignRightSideType, $this->inAssignRightSideNativeType);
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

	/**
	 * The expression computes the value of $target: variable reads inside it
	 * are not sinks, the target's value depends on them. $direct marks the
	 * assigned expression itself (a literal array there gets per-offset
	 * writes), an operand of a combinator is not direct.
	 */
	public function enterValueFlow(VariableWrite $target, bool $direct): self
	{
		return new self($this->isDeep, $this->inAssignRightSideVariableName, $this->inAssignRightSideExpr, $this->inThrow, $this->inAssignRightSideType, $this->inAssignRightSideNativeType, $target, $direct);
	}

	public function getValueFlowTarget(): ?VariableWrite
	{
		return $this->valueFlowTarget;
	}

	public function isValueFlowDirect(): bool
	{
		return $this->valueFlowDirect;
	}

	/**
	 * The expression is the receiver of an offset read or write: a variable
	 * there is read as a container - its offsets are not.
	 */
	public function enterArrayDimFetchRoot(): self
	{
		return new self($this->isDeep, $this->inAssignRightSideVariableName, $this->inAssignRightSideExpr, $this->inThrow, $this->inAssignRightSideType, $this->inAssignRightSideNativeType, $this->valueFlowTarget, false, true);
	}

	public function isArrayDimFetchRoot(): bool
	{
		return $this->arrayDimFetchRoot;
	}

	/**
	 * The expression is an unset() target: its variable (or offset) is not
	 * read, the writes reaching it are discarded.
	 */
	public function enterUnsetTarget(): self
	{
		return new self($this->isDeep, $this->inAssignRightSideVariableName, $this->inAssignRightSideExpr, $this->inThrow, $this->inAssignRightSideType, $this->inAssignRightSideNativeType, null, false, false, true);
	}

	public function isUnsetTarget(): bool
	{
		return $this->unsetTarget;
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use Fiber;
use PhpParser\Node\Expr;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;

final class FiberScope extends MutatingScope
{

	public function toFiberScope(): self
	{
		return $this;
	}

	public function toMutatingScope(): MutatingScope
	{
		return $this->scopeFactory->toMutatingFactory()->create(
			$this->context,
			$this->isDeclareStrictTypes(),
			$this->getFunction(),
			$this->getNamespace(),
			$this->expressionTypes,
			$this->nativeExpressionTypes,
			$this->conditionalExpressions,
			$this->inClosureBindScopeClasses,
			$this->getAnonymousFunctionReflection(),
			$this->isInFirstLevelStatement(),
			$this->currentlyAssignedExpressions,
			$this->currentlyAllowedUndefinedExpressions,
			$this->inFunctionCallsStack,
			$this->afterExtractCall,
			$this->getParentScope(),
			$this->nativeTypesPromoted,
		);
	}

	/** @api */
	public function getType(Expr $node): Type
	{
		/** @var Scope $exprResult */
		$exprResult = Fiber::suspend(
			new ExpressionAnalysisRequest($node, $this),
		);

		return $exprResult->toMutatingScope()->getType($node);
	}

	public function getScopeType(Expr $expr): Type
	{
		return $this->toMutatingScope()->getType($expr);
	}

	public function getScopeNativeType(Expr $expr): Type
	{
		return $this->toMutatingScope()->getNativeType($expr);
	}

	/** @api */
	public function getNativeType(Expr $expr): Type
	{
		/** @var Scope $exprResult */
		$exprResult = Fiber::suspend(
			new ExpressionAnalysisRequest($expr, $this),
		);

		return $exprResult->toMutatingScope()->getNativeType($expr);
	}

	public function getKeepVoidType(Expr $node): Type
	{
		/** @var Scope $exprResult */
		$exprResult = Fiber::suspend(
			new ExpressionAnalysisRequest($node, $this),
		);

		return $exprResult->toMutatingScope()->getKeepVoidType($node);
	}

}

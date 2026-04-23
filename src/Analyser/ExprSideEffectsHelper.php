<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;

#[AutowiredService]
final class ExprSideEffectsHelper
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter]
		private bool $rememberPossiblyImpureFunctionValues,
	)
	{
	}

	public function rememberFuncCall(FuncCall $expr, Scope $scope): bool
	{
		if ($expr->name instanceof Name) {
			if (!$this->reflectionProvider->hasFunction($expr->name, $scope)) {
				return false;
			}

			$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
			$hasSideEffects = $functionReflection->hasSideEffects();
			if ($hasSideEffects->yes()) {
				return false;
			}

			if (!$this->rememberPossiblyImpureFunctionValues && !$hasSideEffects->no()) {
				return false;
			}
		} else {
			$nameType = $scope->getType($expr->name);
			if ($nameType->isCallable()->yes()) {
				$isPure = null;
				foreach ($nameType->getCallableParametersAcceptors($scope) as $variant) {
					$variantIsPure = $variant->isPure();
					$isPure = $isPure === null ? $variantIsPure : $isPure->and($variantIsPure);
				}

				if ($isPure !== null) {
					if ($isPure->no()) {
						return false;
					}

					if (!$this->rememberPossiblyImpureFunctionValues && !$isPure->yes()) {
						return false;
					}
				}
			}
		}

		return !$this->callLikeArgsHaveSideEffects($expr, $scope);
	}

	public function rememberMethodCall(MethodCall $expr, Scope $scope): bool
	{
		if (!$expr->name instanceof Node\Identifier) {
			return false;
		}

		$methodName = $expr->name->toString();
		$calledOnType = $scope->getType($expr->var);
		$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);

		if (
			$methodReflection === null
			|| $methodReflection->hasSideEffects()->yes()
			|| (!$this->rememberPossiblyImpureFunctionValues && !$methodReflection->hasSideEffects()->no())
			|| $this->expressionHasSideEffects($expr->var, $scope)
			|| $this->callLikeArgsHaveSideEffects($expr, $scope)
		) {
			return false;
		}

		return true;
	}

	public function rememberStaticCall(StaticCall $expr, Scope $scope): bool
	{
		if (!$expr->name instanceof Node\Identifier) {
			return false;
		}

		$methodName = $expr->name->toString();
		if ($expr->class instanceof Name) {
			$calledOnType = $scope->resolveTypeByName($expr->class);
		} else {
			$calledOnType = $scope->getType($expr->class);
		}

		$methodReflection = $scope->getMethodReflection($calledOnType, $methodName);

		if (
			$methodReflection === null
			|| $methodReflection->hasSideEffects()->yes()
			|| (!$this->rememberPossiblyImpureFunctionValues && !$methodReflection->hasSideEffects()->no())
			|| ($expr->class instanceof Expr && $this->expressionHasSideEffects($expr->class, $scope))
			|| $this->callLikeArgsHaveSideEffects($expr, $scope)
		) {
			return false;
		}

		return true;
	}

	public function subExpressionsHaveSideEffects(Expr $expr, Scope $scope): bool
	{
		if (
			$expr instanceof MethodCall
			|| $expr instanceof Expr\NullsafeMethodCall
			|| $expr instanceof PropertyFetch
			|| $expr instanceof Expr\NullsafePropertyFetch
			|| $expr instanceof ArrayDimFetch
		) {
			if ($this->expressionHasSideEffects($expr->var, $scope)) {
				return true;
			}
		} elseif (
			$expr instanceof StaticCall
			|| $expr instanceof StaticPropertyFetch
		) {
			if ($expr->class instanceof Expr && $this->expressionHasSideEffects($expr->class, $scope)) {
				return true;
			}
		}

		if ($expr instanceof Expr\CallLike && $this->callLikeArgsHaveSideEffects($expr, $scope)) {
			return true;
		}

		return false;
	}

	private function callLikeArgsHaveSideEffects(Expr\CallLike $expr, Scope $scope): bool
	{
		if ($expr->isFirstClassCallable()) {
			return false;
		}

		foreach ($expr->getArgs() as $arg) {
			if ($this->expressionHasSideEffects($arg->value, $scope)) {
				return true;
			}
		}

		return false;
	}

	private function expressionHasSideEffects(Expr $expr, Scope $scope): bool
	{
		if ($expr instanceof Expr\New_) {
			return true;
		}

		if ($expr instanceof FuncCall) {
			if ($expr->isFirstClassCallable()) {
				return false;
			}
			if (!($expr->name instanceof Name)) {
				return true;
			}

			if (!$this->reflectionProvider->hasFunction($expr->name, $scope)) {
				return true;
			}
			$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
			$hasSideEffects = $functionReflection->hasSideEffects();
			if ($hasSideEffects->yes()) {
				return true;
			}
			if (!$this->rememberPossiblyImpureFunctionValues && !$hasSideEffects->no()) {
				return true;
			}
			foreach ($expr->getArgs() as $arg) {
				if ($this->expressionHasSideEffects($arg->value, $scope)) {
					return true;
				}
			}
			return false;
		}

		if ($expr instanceof MethodCall || $expr instanceof Expr\NullsafeMethodCall) {
			if ($expr->isFirstClassCallable()) {
				return $this->expressionHasSideEffects($expr->var, $scope);
			}
			if (!($expr->name instanceof Node\Identifier)) {
				return true;
			}

			$calledOnType = $scope->getType($expr->var);
			$methodReflection = $scope->getMethodReflection($calledOnType, $expr->name->toString());
			if (
				$methodReflection === null
				|| $methodReflection->hasSideEffects()->yes()
				|| (!$this->rememberPossiblyImpureFunctionValues && !$methodReflection->hasSideEffects()->no())
			) {
				return true;
			}
			foreach ($expr->getArgs() as $arg) {
				if ($this->expressionHasSideEffects($arg->value, $scope)) {
					return true;
				}
			}
			return $this->expressionHasSideEffects($expr->var, $scope);
		}

		if ($expr instanceof StaticCall) {
			if ($expr->isFirstClassCallable()) {
				if ($expr->class instanceof Expr) {
					return $this->expressionHasSideEffects($expr->class, $scope);
				}
				return false;
			}
			if (!($expr->name instanceof Node\Identifier)) {
				return true;
			}

			if ($expr->class instanceof Name) {
				$calledOnType = $scope->resolveTypeByName($expr->class);
			} else {
				$calledOnType = $scope->getType($expr->class);
			}
			$methodReflection = $scope->getMethodReflection($calledOnType, $expr->name->toString());
			if (
				$methodReflection === null
				|| $methodReflection->hasSideEffects()->yes()
				|| (!$this->rememberPossiblyImpureFunctionValues && !$methodReflection->hasSideEffects()->no())
			) {
				return true;
			}
			foreach ($expr->getArgs() as $arg) {
				if ($this->expressionHasSideEffects($arg->value, $scope)) {
					return true;
				}
			}
			if ($expr->class instanceof Expr) {
				return $this->expressionHasSideEffects($expr->class, $scope);
			}
			return false;
		}

		if ($expr instanceof PropertyFetch || $expr instanceof Expr\NullsafePropertyFetch) {
			return $this->expressionHasSideEffects($expr->var, $scope);
		}

		if ($expr instanceof ArrayDimFetch) {
			return $this->expressionHasSideEffects($expr->var, $scope);
		}

		if ($expr instanceof StaticPropertyFetch) {
			if ($expr->class instanceof Expr) {
				return $this->expressionHasSideEffects($expr->class, $scope);
			}
			return false;
		}

		return false;
	}

}

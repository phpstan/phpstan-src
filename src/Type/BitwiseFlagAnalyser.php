<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantIntegerType;

final class BitwiseFlagAnalyser {
	public function __construct(private ReflectionProvider $reflectionProvider) {
	}

	public function exprContainsConstant(Expr $expr, Scope $scope, string $constName): bool
	{
		if ($expr instanceof ConstFetch) {
			$resolveConstantName = $this->reflectionProvider->resolveConstantName($expr->name, $scope);

			if ($resolveConstantName !== $constName)  {
				return false;
			}
		}

		$fqcn = new FullyQualified($constName);
		if ($this->reflectionProvider->hasConstant($fqcn, $scope)) {
			$constant = $this->reflectionProvider->getConstant($fqcn, $scope);

			$valueType = $constant->getValueType();

			if ($valueType instanceof ConstantIntegerType) {
				return $this->exprContainsIntFlag($expr, $scope, $valueType->getValue());
			}
		}

		return false;
	}

	public function exprContainsIntFlag(Expr $expr, Scope $scope, int $flag): bool
	{
		if ($expr instanceof BitwiseOr) {
			return $this->exprContainsFlag($expr->left, $scope, $flag) ||
				$this->exprContainsFlag($expr->right, $scope, $flag);
		}

		$exprType = $scope->getType($expr);

		if ($exprType instanceof ConstantIntegerType){
			return ($exprType->getValue() & $flag) === $flag;
		}

		return false;
	}
}

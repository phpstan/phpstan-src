<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;

final class BitwiseFlagHelper
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function bitwiseOrContainsConstant(Expr $expr, Scope $scope, string $constName): TrinaryLogic
	{
		if ($expr instanceof ConstFetch) {
			if (((string) $expr->name) === $constName) {
				return TrinaryLogic::createYes();
			}

			$resolveConstantName = $this->reflectionProvider->resolveConstantName($expr->name, $scope);
			if ($resolveConstantName !== null) {
				if ($resolveConstantName === $constName) {
					return TrinaryLogic::createYes();
				}
				return TrinaryLogic::createNo();
			}
		}

		if ($expr instanceof BitwiseOr) {
			return TrinaryLogic::createFromBoolean($this->bitwiseOrContainsConstant($expr->left, $scope, $constName)->yes() ||
				$this->bitwiseOrContainsConstant($expr->right, $scope, $constName)->yes());
		}

		$fqcn = new FullyQualified($constName);
		if ($this->reflectionProvider->hasConstant($fqcn, $scope)) {
			$constant = $this->reflectionProvider->getConstant($fqcn, $scope);

			$valueType = $constant->getValueType();

			if ($valueType instanceof ConstantIntegerType) {
				return $this->exprContainsIntFlag($expr, $scope, $valueType->getValue());
			}
		}

		return TrinaryLogic::createNo();
	}

	private function exprContainsIntFlag(Expr $expr, Scope $scope, int $flag): TrinaryLogic
	{
		$exprType = $scope->getType($expr);

		if ($exprType instanceof UnionType) {
			$allTypesContainFlag = true;
			$someTypesContainFlag = false;
			foreach ($exprType->getTypes() as $type) {
				$containsFlag = $this->typeContainsIntFlag($type, $flag);
				if (!$containsFlag->yes()) {
					$allTypesContainFlag = false;
				}

				if (!$containsFlag->yes() && !$containsFlag->maybe()) {
					continue;
				}

				$someTypesContainFlag = true;
			}

			if ($allTypesContainFlag) {
				return TrinaryLogic::createYes();
			}
			if ($someTypesContainFlag) {
				return TrinaryLogic::createMaybe();
			}
			return TrinaryLogic::createNo();
		}

		return $this->typeContainsIntFlag($exprType, $flag);
	}

	private function typeContainsIntFlag(Type $type, int $flag): TrinaryLogic
	{
		if ($type instanceof ConstantIntegerType) {
			if (($type->getValue() & $flag) === $flag) {
				return TrinaryLogic::createYes();
			}
			return TrinaryLogic::createNo();
		}

		if ($type->isInteger()->yes() || $type instanceof MixedType) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createNo();
	}

}

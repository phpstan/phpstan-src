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

final class BitwiseFlagAnalyser
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function exprContainsConstant(Expr $expr, Scope $scope, string $constName): TrinaryLogic
	{
		if ($expr instanceof ConstFetch) {
			$resolveConstantName = $this->reflectionProvider->resolveConstantName($expr->name, $scope);

			if ($resolveConstantName !== $constName) {
				return TrinaryLogic::createNo();
			}
		}

		$fqcn = new FullyQualified($constName);
		if ($this->reflectionProvider->hasConstant($fqcn, $scope)) {
			$constant = $this->reflectionProvider->getConstant($fqcn, $scope);

			$valueType = $constant->getValueType();

			if ($valueType instanceof ConstantIntegerType) {
				return $this->exprContainsIntFlag($expr, $scope, $valueType->getValue());
			}

			// $exprType = $scope->getType($expr);
			//return $exprType->isSuperTypeOf($valueType);
		}

		return TrinaryLogic::createNo();
	}

	private function exprContainsIntFlag(Expr $expr, Scope $scope, int $flag): TrinaryLogic
	{
		if ($expr instanceof BitwiseOr) {
			return TrinaryLogic::createFromBoolean($this->exprContainsIntFlag($expr->left, $scope, $flag)->yes() ||
				$this->exprContainsIntFlag($expr->right, $scope, $flag)->yes());
		}

		$exprType = $scope->getType($expr);

		if ($exprType instanceof ConstantIntegerType) {
			return $this->typeContainsIntFlag($exprType, $flag);
		}

		if ($exprType instanceof UnionType) {
			$containsMaybe = false;

			foreach ($exprType->getTypes() as $type) {
				$containsFlag = $this->typeContainsIntFlag($type, $flag);
				if ($containsFlag->yes()) {
					return TrinaryLogic::createYes();
				}
				if (!$containsFlag->maybe()) {
					continue;
				}

				$containsMaybe = true;
			}

			if ($containsMaybe) {
				return TrinaryLogic::createMaybe();
			}
		}

		$integerType = new IntegerType();
		$mixedType = new MixedType();
		if ($integerType->isSuperTypeOf($exprType)->yes() || $exprType instanceof MixedType) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createNo();
	}

	private function typeContainsIntFlag(Type $type, int $flag): TrinaryLogic
	{
		if ($type instanceof ConstantIntegerType) {
			if (($type->getValue() & $flag) === $flag) {
				return TrinaryLogic::createYes();
			}
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

}

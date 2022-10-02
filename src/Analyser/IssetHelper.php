<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function is_string;

class IssetHelper
{

	public function __construct(
		private readonly PropertyReflectionFinder $propertyReflectionFinder,
		private readonly bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	/**
	 * @param callable(Type): ?bool $typeCallback
	 */
	public function isset(Expr $expr, Scope $scope, callable $typeCallback, ?bool $result = null): ?bool
	{
		// mirrored in PHPStan\Rules\IssetCheck
		if ($expr instanceof Expr\Variable && is_string($expr->name)) {
			$hasVariable = $scope->hasVariableType($expr->name);
			if ($hasVariable->maybe()) {
				return null;
			}

			if ($result === null) {
				if ($hasVariable->yes()) {
					if ($expr->name === '_SESSION') {
						return null;
					}

					return $typeCallback($scope->getVariableType($expr->name));
				}

				return false;
			}

			return $result;
		} elseif ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			$type = $this->treatPhpDocTypesAsCertain
				? $scope->getType($expr->var)
				: $scope->getNativeType($expr->var);
			$dimType = $this->treatPhpDocTypesAsCertain
				? $scope->getType($expr->dim)
				: $scope->getNativeType($expr->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);
			if (!$type->isOffsetAccessible()->yes()) {
				return $result ?? $this->issetCheckUndefined($expr->var, $scope);
			}

			if ($hasOffsetValue->no()) {
				if ($result !== null) {
					return $result;
				}

				return false;
			}

			if ($hasOffsetValue->maybe()) {
				return null;
			}

			// If offset is cannot be null, store this error message and see if one of the earlier offsets is.
			// E.g. $array['a']['b']['c'] ?? null; is a valid coalesce if a OR b or C might be null.
			if ($hasOffsetValue->yes()) {
				if ($result !== null) {
					return $result;
				}

				$result = $typeCallback($type->getOffsetValueType($dimType));

				if ($result !== null) {
					return $this->isset($expr->var, $scope, $typeCallback, $result);
				}
			}

			// Has offset, it is nullable
			return null;

		} elseif ($expr instanceof Expr\PropertyFetch || $expr instanceof Expr\StaticPropertyFetch) {

			$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $scope);

			if ($propertyReflection === null) {
				if ($expr instanceof Expr\PropertyFetch) {
					return $this->issetCheckUndefined($expr->var, $scope);
				}

				if ($expr->class instanceof Expr) {
					return $this->issetCheckUndefined($expr->class, $scope);
				}

				return null;
			}

			if (!$propertyReflection->isNative()) {
				if ($expr instanceof Expr\PropertyFetch) {
					return $this->issetCheckUndefined($expr->var, $scope);
				}

				if ($expr->class instanceof Expr) {
					return $this->issetCheckUndefined($expr->class, $scope);
				}

				return null;
			}

			$nativeType = $propertyReflection->getNativeType();
			if (!$nativeType instanceof MixedType) {
				if (!$scope->isSpecified($expr)) {
					if ($expr instanceof Expr\PropertyFetch) {
						return $this->issetCheckUndefined($expr->var, $scope);
					}

					if ($expr->class instanceof Expr) {
						return $this->issetCheckUndefined($expr->class, $scope);
					}

					return null;
				}
			}

			if ($result !== null) {
				return $result;
			}

			$result = $typeCallback($propertyReflection->getWritableType());
			if ($result !== null) {
				if ($expr instanceof Expr\PropertyFetch) {
					return $this->isset($expr->var, $scope, $typeCallback, $result);
				}

				if ($expr->class instanceof Expr) {
					return $this->isset($expr->class, $scope, $typeCallback, $result);
				}
			}

			return $result;
		}

		if ($result !== null) {
			return $result;
		}

		return $typeCallback($scope->getType($expr));
	}

	private function issetCheckUndefined(Expr $expr, Scope $scope): ?bool
	{
		if ($expr instanceof Expr\Variable && is_string($expr->name)) {
			$hasVariable = $scope->hasVariableType($expr->name);
			if (!$hasVariable->no()) {
				return null;
			}

			return false;
		}

		if ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			$type = $scope->getType($expr->var);
			$dimType = $scope->getType($expr->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);
			if (!$type->isOffsetAccessible()->yes()) {
				return $this->issetCheckUndefined($expr->var, $scope);
			}

			if (!$hasOffsetValue->no()) {
				return $this->issetCheckUndefined($expr->var, $scope);
			}

			return false;
		}

		if ($expr instanceof Expr\PropertyFetch) {
			return $this->issetCheckUndefined($expr->var, $scope);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			return $this->issetCheckUndefined($expr->class, $scope);
		}

		return null;
	}

}

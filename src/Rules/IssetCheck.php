<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class IssetCheck
{

	private \PHPStan\Rules\Properties\PropertyDescriptor $propertyDescriptor;

	private \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder;

	public function __construct(
		PropertyDescriptor $propertyDescriptor,
		PropertyReflectionFinder $propertyReflectionFinder
	)
	{
		$this->propertyDescriptor = $propertyDescriptor;
		$this->propertyReflectionFinder = $propertyReflectionFinder;
	}

	public function check(Expr $expr, Scope $scope, string $operatorDescription, ?RuleError $error = null): ?RuleError
	{
		if ($expr instanceof Node\Expr\Variable && is_string($expr->name)) {
			$hasVariable = $scope->hasVariableType($expr->name);
			if ($hasVariable->maybe()) {
				return null;
			}

			return $error;
		} elseif ($expr instanceof Node\Expr\ArrayDimFetch && $expr->dim !== null) {

			$type = $scope->getType($expr->var);
			$dimType = $scope->getType($expr->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);
			if (!$type->isOffsetAccessible()->yes()) {
				return $error;
			}

			if ($hasOffsetValue->no()) {
				return $error ?? RuleErrorBuilder::message(
					sprintf(
						'Offset %s on %s %s does not exist.',
						$dimType->describe(VerbosityLevel::value()),
						$type->describe(VerbosityLevel::value()),
						$operatorDescription
					)
				)->build();
			}

			if ($hasOffsetValue->maybe()) {
				return null;
			}

			// If offset is cannot be null, store this error message and see if one of the earlier offsets is.
			// E.g. $array['a']['b']['c'] ?? null; is a valid coalesce if a OR b or C might be null.
			if ($hasOffsetValue->yes()) {

				$error = $error ?? $this->generateError($type->getOffsetValueType($dimType), sprintf(
					'Offset %s on %s %s always exists and',
					$dimType->describe(VerbosityLevel::value()),
					$type->describe(VerbosityLevel::value()),
					$operatorDescription
				));

				if ($error !== null) {
					return $this->check($expr->var, $scope, $operatorDescription, $error);
				}
			}

			// Has offset, it is nullable
			return null;

		} elseif ($expr instanceof Node\Expr\PropertyFetch || $expr instanceof Node\Expr\StaticPropertyFetch) {

			$propertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($expr, $scope);
			if (count($propertyReflections) === 0) {
				return null;
			}

			foreach ($propertyReflections as $propertyReflection) {
				if (!$propertyReflection->isNative()) {
					return null;
				}

				$nativeType = $propertyReflection->getNativeType();
				if (!$nativeType instanceof MixedType
					&&
					!$scope->isSpecified($expr)
				) {
					return null;
				}

				$propertyDescription = $this->propertyDescriptor->describeProperty($propertyReflection, $expr);
				$propertyType = $propertyReflection->getWritableType();

				$error = $error ?? $this->generateError(
					$propertyType,
					sprintf('%s (%s) %s', $propertyDescription, $propertyType->describe(VerbosityLevel::typeOnly()), $operatorDescription)
				);

				if ($error === null) {
					continue;
				}

				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->check($expr->var, $scope, $operatorDescription, $error);
				}

				if ($expr->class instanceof Expr) {
					return $this->check($expr->class, $scope, $operatorDescription, $error);
				}
			}

			return $error;
		}

		return $error ?? $this->generateError($scope->getType($expr), sprintf('Expression %s', $operatorDescription));
	}

	private function generateError(Type $type, string $message): ?RuleError
	{
		$nullType = new NullType();

		if ($type->equals($nullType)) {
			return RuleErrorBuilder::message(
				sprintf('%s is always null.', $message)
			)->build();
		}

		if ($type->isSuperTypeOf($nullType)->no()) {
			return RuleErrorBuilder::message(
				sprintf('%s is not nullable.', $message)
			)->build();
		}

		return null;
	}

}

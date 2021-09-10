<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\MixedType;
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

	/**
	 * @param callable(Type): ?string $typeMessageCallback
	 */
	public function check(Expr $expr, Scope $scope, string $operatorDescription, callable $typeMessageCallback, ?RuleError $error = null): ?RuleError
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
				), $typeMessageCallback);

				if ($error !== null) {
					return $this->check($expr->var, $scope, $operatorDescription, $typeMessageCallback, $error);
				}
			}

			// Has offset, it is nullable
			return null;

		} elseif ($expr instanceof Node\Expr\PropertyFetch || $expr instanceof Node\Expr\StaticPropertyFetch) {

			$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $scope);

			if ($propertyReflection === null) {
				return null;
			}

			if (!$propertyReflection->isNative()) {
				return null;
			}

			$nativeType = $propertyReflection->getNativeType();
			if (!$nativeType instanceof MixedType) {
				if (!$scope->isSpecified($expr)) {
					return null;
				}
			}

			$propertyDescription = $this->propertyDescriptor->describeProperty($propertyReflection, $expr);
			$propertyType = $propertyReflection->getWritableType();

			$error = $error ?? $this->generateError(
				$propertyReflection->getWritableType(),
				sprintf('%s (%s) %s', $propertyDescription, $propertyType->describe(VerbosityLevel::typeOnly()), $operatorDescription),
				$typeMessageCallback
			);

			if ($error !== null) {
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->check($expr->var, $scope, $operatorDescription, $typeMessageCallback, $error);
				}

				if ($expr->class instanceof Expr) {
					return $this->check($expr->class, $scope, $operatorDescription, $typeMessageCallback, $error);
				}
			}

			return $error;
		}

		return $error ?? $this->generateError($scope->getType($expr), sprintf('Expression %s', $operatorDescription), $typeMessageCallback);
	}

	/**
	 * @param callable(Type): ?string $typeMessageCallback
	 */
	private function generateError(Type $type, string $message, callable $typeMessageCallback): ?RuleError
	{
		$typeMessage = $typeMessageCallback($type);
		if ($typeMessage === null) {
			return null;
		}

		return RuleErrorBuilder::message(
			sprintf('%s %s.', $message, $typeMessage)
		)->build();
	}

}

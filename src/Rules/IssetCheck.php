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
use function is_string;
use function sprintf;

class IssetCheck
{

	public function __construct(
		private PropertyDescriptor $propertyDescriptor,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private bool $checkAdvancedIsset,
		private bool $treatPhpDocTypesAsCertain,
		private bool $strictUnnecessaryNullsafePropertyFetch,
	)
	{
	}

	/**
	 * @param callable(Type): ?string $typeMessageCallback
	 */
	public function check(Expr $expr, Scope $scope, string $operatorDescription, callable $typeMessageCallback, ?RuleError $error = null): ?RuleError
	{
		// mirrored in PHPStan\Analyser\MutatingScope::issetCheck()
		if ($expr instanceof Node\Expr\Variable && is_string($expr->name)) {
			$hasVariable = $scope->hasVariableType($expr->name);
			if ($hasVariable->maybe()) {
				return null;
			}

			if ($error === null) {
				if ($hasVariable->yes()) {
					if ($expr->name === '_SESSION') {
						return null;
					}

					return $this->generateError(
						$this->treatPhpDocTypesAsCertain ? $scope->getType($expr) : $scope->getNativeType($expr),
						sprintf('Variable $%s %s always exists and', $expr->name, $operatorDescription),
						$typeMessageCallback,
					);
				}

				return RuleErrorBuilder::message(sprintf('Variable $%s %s is never defined.', $expr->name, $operatorDescription))->build();
			}

			return $error;
		} elseif ($expr instanceof Node\Expr\ArrayDimFetch && $expr->dim !== null) {
			$type = $this->treatPhpDocTypesAsCertain
				? $scope->getType($expr->var)
				: $scope->getNativeType($expr->var);
			if (!$type->isOffsetAccessible()->yes()) {
				return $error ?? $this->checkUndefined($expr->var, $scope, $operatorDescription);
			}

			$dimType = $this->treatPhpDocTypesAsCertain
				? $scope->getType($expr->dim)
				: $scope->getNativeType($expr->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);
			if ($hasOffsetValue->no()) {
				if (!$this->checkAdvancedIsset) {
					return null;
				}

				return RuleErrorBuilder::message(
					sprintf(
						'Offset %s on %s %s does not exist.',
						$dimType->describe(VerbosityLevel::value()),
						$type->describe(VerbosityLevel::value()),
						$operatorDescription,
					),
				)->build();
			}

			// If offset cannot be null, store this error message and see if one of the earlier offsets is.
			// E.g. $array['a']['b']['c'] ?? null; is a valid coalesce if a OR b or C might be null.
			if ($hasOffsetValue->yes() || $scope->hasExpressionType($expr)->yes()) {
				if (!$this->checkAdvancedIsset) {
					return null;
				}

				$error ??= $this->generateError($type->getOffsetValueType($dimType), sprintf(
					'Offset %s on %s %s always exists and',
					$dimType->describe(VerbosityLevel::value()),
					$type->describe(VerbosityLevel::value()),
					$operatorDescription,
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
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->checkUndefined($expr->var, $scope, $operatorDescription);
				}

				if ($expr->class instanceof Expr) {
					return $this->checkUndefined($expr->class, $scope, $operatorDescription);
				}

				return null;
			}

			if (!$propertyReflection->isNative()) {
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->checkUndefined($expr->var, $scope, $operatorDescription);
				}

				if ($expr->class instanceof Expr) {
					return $this->checkUndefined($expr->class, $scope, $operatorDescription);
				}

				return null;
			}

			$nativeType = $propertyReflection->getNativeType();
			if (!$nativeType instanceof MixedType) {
				if (!$scope->hasExpressionType($expr)->yes()) {
					if ($expr instanceof Node\Expr\PropertyFetch) {
						return $this->checkUndefined($expr->var, $scope, $operatorDescription);
					}

					if ($expr->class instanceof Expr) {
						return $this->checkUndefined($expr->class, $scope, $operatorDescription);
					}

					return null;
				}
			}

			$propertyDescription = $this->propertyDescriptor->describeProperty($propertyReflection, $scope, $expr);
			$propertyType = $propertyReflection->getWritableType();
			if ($error !== null) {
				return $error;
			}
			if (!$this->checkAdvancedIsset) {
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->checkUndefined($expr->var, $scope, $operatorDescription);
				}

				if ($expr->class instanceof Expr) {
					return $this->checkUndefined($expr->class, $scope, $operatorDescription);
				}

				return null;
			}

			$error = $this->generateError(
				$propertyReflection->getWritableType(),
				sprintf('%s (%s) %s', $propertyDescription, $propertyType->describe(VerbosityLevel::typeOnly()), $operatorDescription),
				$typeMessageCallback,
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

		if ($error !== null) {
			return $error;
		}

		if (!$this->checkAdvancedIsset) {
			return null;
		}

		$error = $this->generateError($this->treatPhpDocTypesAsCertain ? $scope->getType($expr) : $scope->getNativeType($expr), sprintf('Expression %s', $operatorDescription), $typeMessageCallback);
		if ($error !== null) {
			return $error;
		}

		if ($expr instanceof Expr\NullsafePropertyFetch) {
			if (!$this->strictUnnecessaryNullsafePropertyFetch) {
				return null;
			}

			if ($expr->name instanceof Node\Identifier) {
				return RuleErrorBuilder::message(sprintf('Using nullsafe property access "?->%s" %s is unnecessary. Use -> instead.', $expr->name->name, $operatorDescription))->build();
			}

			return RuleErrorBuilder::message(sprintf('Using nullsafe property access "?->(Expression)" %s is unnecessary. Use -> instead.', $operatorDescription))->build();
		}

		return null;
	}

	private function checkUndefined(Expr $expr, Scope $scope, string $operatorDescription): ?RuleError
	{
		if ($expr instanceof Node\Expr\Variable && is_string($expr->name)) {
			$hasVariable = $scope->hasVariableType($expr->name);
			if (!$hasVariable->no()) {
				return null;
			}

			return RuleErrorBuilder::message(sprintf('Variable $%s %s is never defined.', $expr->name, $operatorDescription))->build();
		}

		if ($expr instanceof Node\Expr\ArrayDimFetch && $expr->dim !== null) {
			$type = $this->treatPhpDocTypesAsCertain ? $scope->getType($expr->var) : $scope->getNativeType($expr->var);
			$dimType = $this->treatPhpDocTypesAsCertain ? $scope->getType($expr->dim) : $scope->getNativeType($expr->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);
			if (!$type->isOffsetAccessible()->yes()) {
				return $this->checkUndefined($expr->var, $scope, $operatorDescription);
			}

			if (!$hasOffsetValue->no()) {
				return $this->checkUndefined($expr->var, $scope, $operatorDescription);
			}

			return RuleErrorBuilder::message(
				sprintf(
					'Offset %s on %s %s does not exist.',
					$dimType->describe(VerbosityLevel::value()),
					$type->describe(VerbosityLevel::value()),
					$operatorDescription,
				),
			)->build();
		}

		if ($expr instanceof Expr\PropertyFetch) {
			return $this->checkUndefined($expr->var, $scope, $operatorDescription);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			return $this->checkUndefined($expr->class, $scope, $operatorDescription);
		}

		return null;
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
			sprintf('%s %s.', $message, $typeMessage),
		)->build();
	}

}

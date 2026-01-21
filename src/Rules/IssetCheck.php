<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function is_string;
use function sprintf;
use function str_starts_with;

/**
 * @phpstan-type ErrorIdentifier = 'empty'|'isset'|'nullCoalesce'
 */
#[AutowiredService]
final class IssetCheck
{

	public function __construct(
		private PropertyDescriptor $propertyDescriptor,
		private PropertyReflectionFinder $propertyReflectionFinder,
		#[AutowiredParameter]
		private bool $checkAdvancedIsset,
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	/**
	 * @param ErrorIdentifier $identifier
	 * @param callable(Type): ?string $typeMessageCallback
	 */
	public function check(Expr $expr, Scope $scope, string $operatorDescription, string $identifier, callable $typeMessageCallback, ?IdentifierRuleError $error = null): ?IdentifierRuleError
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

					$type = $this->treatPhpDocTypesAsCertain ? $scope->getScopeType($expr) : $scope->getScopeNativeType($expr);
					if (!$type instanceof NeverType) {
						return $this->generateError(
							$type,
							sprintf('Variable $%s %s always exists and', $expr->name, $operatorDescription),
							$typeMessageCallback,
							$identifier,
							'variable',
						);
					}
				}

				return RuleErrorBuilder::message(sprintf('Variable $%s %s is never defined.', $expr->name, $operatorDescription))
					->identifier(sprintf('%s.variable', $identifier))
					->build();
			}

			return $error;
		} elseif ($expr instanceof Node\Expr\ArrayDimFetch && $expr->dim !== null) {
			$type = $this->treatPhpDocTypesAsCertain
				? $scope->getScopeType($expr->var)
				: $scope->getScopeNativeType($expr->var);
			if (!$type->isOffsetAccessible()->yes()) {
				return $error ?? $this->checkUndefined($expr->var, $scope, $operatorDescription, $identifier);
			}

			$dimType = $this->treatPhpDocTypesAsCertain
				? $scope->getScopeType($expr->dim)
				: $scope->getScopeNativeType($expr->dim);
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
				)->identifier(sprintf('%s.offset', $identifier))->build();
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
				), $typeMessageCallback, $identifier, 'offset');

				if ($error !== null) {
					return $this->check($expr->var, $scope, $operatorDescription, $identifier, $typeMessageCallback, $error);
				}
			}

			// Has offset, it is nullable
			return null;

		} elseif ($expr instanceof Node\Expr\PropertyFetch || $expr instanceof Node\Expr\StaticPropertyFetch) {

			$propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $scope->toMutatingScope());

			if ($propertyReflection === null) {
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->checkUndefined($expr->var, $scope, $operatorDescription, $identifier);
				}

				if ($expr->class instanceof Expr) {
					return $this->checkUndefined($expr->class, $scope, $operatorDescription, $identifier);
				}

				return null;
			}

			if (!$propertyReflection->isNative()) {
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->checkUndefined($expr->var, $scope, $operatorDescription, $identifier);
				}

				if ($expr->class instanceof Expr) {
					return $this->checkUndefined($expr->class, $scope, $operatorDescription, $identifier);
				}

				return null;
			}

			if ($propertyReflection->hasNativeType() && !$propertyReflection->isVirtual()->yes()) {
				if (
					$expr instanceof Node\Expr\PropertyFetch
					&& $expr->name instanceof Node\Identifier
					&& $expr->var instanceof Expr\Variable
					&& $expr->var->name === 'this'
					&& $scope->hasExpressionType(new PropertyInitializationExpr($propertyReflection->getName()))->yes()
				) {
					return $this->generateError(
						$propertyReflection->getNativeType(),
						sprintf(
							'%s %s',
							$this->propertyDescriptor->describeProperty($propertyReflection, $scope, $expr),
							$operatorDescription,
						),
						static function (Type $type) use ($typeMessageCallback): ?string {
							$originalMessage = $typeMessageCallback($type);
							if ($originalMessage === null) {
								return null;
							}

							if (str_starts_with($originalMessage, 'is not')) {
								return sprintf('%s nor uninitialized', $originalMessage);
							}

							return sprintf('%s and initialized', $originalMessage);
						},
						$identifier,
						'initializedProperty',
					);
				}

				if (!$scope->hasExpressionType($expr)->yes()) {
					if ($expr instanceof Node\Expr\PropertyFetch) {
						return $this->checkUndefined($expr->var, $scope, $operatorDescription, $identifier);
					}

					if ($expr->class instanceof Expr) {
						return $this->checkUndefined($expr->class, $scope, $operatorDescription, $identifier);
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
					return $this->checkUndefined($expr->var, $scope, $operatorDescription, $identifier);
				}

				if ($expr->class instanceof Expr) {
					return $this->checkUndefined($expr->class, $scope, $operatorDescription, $identifier);
				}

				return null;
			}

			$error = $this->generateError(
				$propertyReflection->getWritableType(),
				sprintf('%s (%s) %s', $propertyDescription, $propertyType->describe(VerbosityLevel::typeOnly()), $operatorDescription),
				$typeMessageCallback,
				$identifier,
				'property',
			);

			if ($error !== null) {
				if ($expr instanceof Node\Expr\PropertyFetch) {
					return $this->check($expr->var, $scope, $operatorDescription, $identifier, $typeMessageCallback, $error);
				}

				if ($expr->class instanceof Expr) {
					return $this->check($expr->class, $scope, $operatorDescription, $identifier, $typeMessageCallback, $error);
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

		$error = $this->generateError(
			$this->treatPhpDocTypesAsCertain ? $scope->getScopeType($expr) : $scope->getScopeNativeType($expr),
			sprintf('Expression %s', $operatorDescription),
			$typeMessageCallback,
			$identifier,
			'expr',
		);
		if ($error !== null) {
			return $error;
		}

		if ($expr instanceof Expr\NullsafePropertyFetch) {
			if ($expr->name instanceof Node\Identifier) {
				return RuleErrorBuilder::message(sprintf('Using nullsafe property access "?->%s" %s is unnecessary. Use -> instead.', $expr->name->name, $operatorDescription))
					->identifier('nullsafe.neverNull')
					->build();
			}

			return RuleErrorBuilder::message(sprintf('Using nullsafe property access "?->(Expression)" %s is unnecessary. Use -> instead.', $operatorDescription))
				->identifier('nullsafe.neverNull')
				->build();
		}

		return null;
	}

	/**
	 * @param ErrorIdentifier $identifier
	 */
	private function checkUndefined(Expr $expr, Scope $scope, string $operatorDescription, string $identifier): ?IdentifierRuleError
	{
		if ($expr instanceof Node\Expr\Variable && is_string($expr->name)) {
			$hasVariable = $scope->hasVariableType($expr->name);
			if (!$hasVariable->no()) {
				return null;
			}

			return RuleErrorBuilder::message(sprintf('Variable $%s %s is never defined.', $expr->name, $operatorDescription))
				->identifier(sprintf('%s.variable', $identifier))
				->build();
		}

		if ($expr instanceof Node\Expr\ArrayDimFetch && $expr->dim !== null) {
			$type = $this->treatPhpDocTypesAsCertain ? $scope->getScopeType($expr->var) : $scope->getScopeNativeType($expr->var);
			$dimType = $this->treatPhpDocTypesAsCertain ? $scope->getScopeType($expr->dim) : $scope->getScopeNativeType($expr->dim);
			$hasOffsetValue = $type->hasOffsetValueType($dimType);
			if (!$type->isOffsetAccessible()->yes()) {
				return $this->checkUndefined($expr->var, $scope, $operatorDescription, $identifier);
			}

			if (!$hasOffsetValue->no()) {
				return $this->checkUndefined($expr->var, $scope, $operatorDescription, $identifier);
			}

			return RuleErrorBuilder::message(
				sprintf(
					'Offset %s on %s %s does not exist.',
					$dimType->describe(VerbosityLevel::value()),
					$type->describe(VerbosityLevel::value()),
					$operatorDescription,
				),
			)->identifier(sprintf('%s.offset', $identifier))->build();
		}

		if ($expr instanceof Expr\PropertyFetch) {
			return $this->checkUndefined($expr->var, $scope, $operatorDescription, $identifier);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			return $this->checkUndefined($expr->class, $scope, $operatorDescription, $identifier);
		}

		return null;
	}

	/**
	 * @param callable(Type): ?string $typeMessageCallback
	 * @param ErrorIdentifier $identifier
	 * @param 'variable'|'offset'|'property'|'expr'|'initializedProperty' $identifierSecondPart
	 */
	private function generateError(Type $type, string $message, callable $typeMessageCallback, string $identifier, string $identifierSecondPart): ?IdentifierRuleError
	{
		$typeMessage = $typeMessageCallback($type);
		if ($typeMessage === null) {
			return null;
		}

		return RuleErrorBuilder::message(
			sprintf('%s %s.', $message, $typeMessage),
		)->identifier(sprintf('%s.%s', $identifier, $identifierSecondPart))->build();
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\IssetabilityResolution;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Rules\Properties\PropertyDescriptor;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
use function str_starts_with;

/**
 * Renders the isset/empty/?? "does it make sense" errors from the single
 * IssetabilityResolution the engine already computed. The chain is walked and
 * resolved once (IssetabilityDescriptor::resolve); this only projects the
 * resolved facts into messages - it never re-walks the AST nor re-resolves types.
 *
 * @phpstan-type ErrorIdentifier = 'empty'|'isset'|'nullCoalesce'
 */
#[AutowiredService]
final class IssetCheck
{

	public function __construct(
		private PropertyDescriptor $propertyDescriptor,
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
	public function check(ExpressionResult $exprResult, Scope $scope, string $operatorDescription, string $identifier, callable $typeMessageCallback): ?IdentifierRuleError
	{
		$walkScope = $scope->toWalkScope();
		$resolution = $exprResult->getIssetabilityResolution($walkScope, !$this->treatPhpDocTypesAsCertain, true);

		return $this->doCheck($resolution, $walkScope, $operatorDescription, $identifier, $typeMessageCallback, null);
	}

	/**
	 * @param ErrorIdentifier $identifier
	 * @param callable(Type): ?string $typeMessageCallback
	 */
	private function doCheck(IssetabilityResolution $resolution, MutatingScope $scope, string $operatorDescription, string $identifier, callable $typeMessageCallback, ?IdentifierRuleError $error): ?IdentifierRuleError
	{
		$link = $resolution->getLink();
		$inner = $resolution->getInner();

		if ($link->isVariable()) {
			$hasVariable = $link->getHasVariable();
			if ($hasVariable->maybe()) {
				return null;
			}

			if ($error === null) {
				if ($hasVariable->yes()) {
					if ($link->getVariableName() === '_SESSION') {
						return null;
					}

					$type = $link->getValueType();
					if (!$type instanceof NeverType) {
						return $this->generateError(
							$type,
							sprintf('Variable $%s %s always exists and', $link->getVariableName(), $operatorDescription),
							$typeMessageCallback,
							$identifier,
							'variable',
						);
					}
				}

				return RuleErrorBuilder::message(sprintf('Variable $%s %s is never defined.', $link->getVariableName(), $operatorDescription))
					->identifier(sprintf('%s.variable', $identifier))
					->build();
			}

			return $error;
		}

		if ($link->isOffset()) {
			$type = $link->getVarType();
			if (!$link->getIsOffsetAccessible()->yes()) {
				return $error ?? $this->checkUndefinedInner($inner, $scope, $operatorDescription, $identifier);
			}

			$dimType = $link->getDimType();
			$hasOffsetValue = $link->getHasOffsetValue();
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
			if ($hasOffsetValue->yes() || $link->hasExpressionTypeOfExpr()) {
				if (!$this->checkAdvancedIsset) {
					return null;
				}

				$error ??= $this->generateError($link->getValueType(), sprintf(
					'Offset %s on %s %s always exists and',
					$dimType->describe(VerbosityLevel::value()),
					$type->describe(VerbosityLevel::value()),
					$operatorDescription,
				), $typeMessageCallback, $identifier, 'offset');

				if ($error !== null) {
					return $inner !== null ? $this->doCheck($inner, $scope, $operatorDescription, $identifier, $typeMessageCallback, $error) : $error;
				}
			}

			// Has offset, it is nullable
			return null;
		}

		if ($link->isProperty()) {
			$reflection = $link->getPropertyReflection();
			$propertyFetch = $link->getPropertyFetch();

			if ($reflection === null || !$link->isReflectionNative()) {
				return $this->checkUndefinedInner($inner, $scope, $operatorDescription, $identifier);
			}

			if ($link->hasNativeType() && !$link->isVirtual()->yes()) {
				if ($link->isInitializedThisProperty()) {
					return $this->generateError(
						$link->getNativeType(),
						sprintf('%s %s', $this->propertyDescriptor->describeProperty($reflection, $scope, $propertyFetch), $operatorDescription),
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

				if (
					!$link->hasExpressionTypeOfFetch()
					&& $link->nativeReflectionExists()
					&& !$link->nativeHasDefaultValue()
					&& (!$link->nativeIsPromoted() || (!$link->nativeIsReadOnly() && !$link->nativeIsHooked()))
				) {
					return null;
				}
			}

			$propertyDescription = $this->propertyDescriptor->describeProperty($reflection, $scope, $propertyFetch);
			$propertyType = $reflection->getWritableType();
			if ($error !== null) {
				return $inner !== null
					? $this->doCheck($inner, $scope, $operatorDescription, $identifier, $typeMessageCallback, $error)
					: $error;
			}
			if (!$this->checkAdvancedIsset) {
				return $this->checkUndefinedInner($inner, $scope, $operatorDescription, $identifier);
			}

			$error = $this->generateError(
				$propertyType,
				sprintf('%s (%s) %s', $propertyDescription, $propertyType->describe(VerbosityLevel::typeOnly()), $operatorDescription),
				$typeMessageCallback,
				$identifier,
				'property',
			);

			if ($error !== null && $inner !== null) {
				return $this->doCheck($inner, $scope, $operatorDescription, $identifier, $typeMessageCallback, $error);
			}

			return $error;
		}

		// leaf - an arbitrary base expression that is not a chain link
		if ($error !== null) {
			return $error;
		}

		if (!$this->checkAdvancedIsset) {
			return null;
		}

		$error = $this->generateError(
			$link->getValueType(),
			sprintf('Expression %s', $operatorDescription),
			$typeMessageCallback,
			$identifier,
			'expr',
		);
		if ($error !== null) {
			return $error;
		}

		if ($link->leafIsNullsafePropertyFetch()) {
			$leafExpr = $link->getLeafExpr();
			if ($leafExpr instanceof NullsafePropertyFetch && $leafExpr->name instanceof Identifier) {
				return RuleErrorBuilder::message(sprintf('Using nullsafe property access "?->%s" %s is unnecessary. Use -> instead.', $leafExpr->name->name, $operatorDescription))
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
	private function checkUndefinedInner(?IssetabilityResolution $resolution, MutatingScope $scope, string $operatorDescription, string $identifier): ?IdentifierRuleError
	{
		if ($resolution === null) {
			return null;
		}

		$link = $resolution->getLink();
		$inner = $resolution->getInner();

		if ($link->isVariable()) {
			if (!$link->getHasVariable()->no()) {
				return null;
			}

			return RuleErrorBuilder::message(sprintf('Variable $%s %s is never defined.', $link->getVariableName(), $operatorDescription))
				->identifier(sprintf('%s.variable', $identifier))
				->build();
		}

		if ($link->isOffset()) {
			if (!$link->getIsOffsetAccessible()->yes()) {
				return $this->checkUndefinedInner($inner, $scope, $operatorDescription, $identifier);
			}

			if (!$link->getHasOffsetValue()->no()) {
				return $this->checkUndefinedInner($inner, $scope, $operatorDescription, $identifier);
			}

			return RuleErrorBuilder::message(
				sprintf(
					'Offset %s on %s %s does not exist.',
					$link->getDimType()->describe(VerbosityLevel::value()),
					$link->getVarType()->describe(VerbosityLevel::value()),
					$operatorDescription,
				),
			)->identifier(sprintf('%s.offset', $identifier))->build();
		}

		if ($link->isProperty()) {
			return $this->checkUndefinedInner($inner, $scope, $operatorDescription, $identifier);
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

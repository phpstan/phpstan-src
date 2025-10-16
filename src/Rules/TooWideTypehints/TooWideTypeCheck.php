<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node\Expr\ConstFetch;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Node\Property\PropertyAssign;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\SimultaneousTypeTraverser;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use function count;
use function in_array;
use function lcfirst;
use function sprintf;
use function strtolower;

#[AutowiredService]
final class TooWideTypeCheck
{

	public function __construct(
		private PropertyReflectionFinder $propertyReflectionFinder,
		#[AutowiredParameter(ref: '%featureToggles.reportTooWideBool%')]
		private bool $reportTooWideBool,
		#[AutowiredParameter(ref: '%featureToggles.reportNestedTooWideType%')]
		private bool $reportNestedTooWideType,
	)
	{
	}

	/**
	 * @param PropertyAssign[] $propertyAssigns
	 * @return list<IdentifierRuleError>
	 */
	public function checkProperty(
		ClassPropertyNode $node,
		ClassReflection $declaringClass,
		array $propertyAssigns,
		Type $nativePropertyType,
		Type $phpDocPropertyType,
		string $propertyDescription,
		Scope $scope,
	): array
	{
		$errors = [];

		$assignedTypes = [];
		foreach ($propertyAssigns as $assign) {
			$assignNode = $assign->getAssign();
			$assignPropertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($assignNode->getPropertyFetch(), $assign->getScope());
			if (count($assignPropertyReflections) === 0) {
				return [];
			}
			foreach ($assignPropertyReflections as $assignPropertyReflection) {
				if ($node->getName() !== $assignPropertyReflection->getName()) {
					continue;
				}
				if ($declaringClass->getName() !== $assignPropertyReflection->getDeclaringClass()->getName()) {
					continue;
				}

				$assignedTypes[] = $assignPropertyReflection->getScope()->getType($assignNode->getAssignedExpr());
			}
		}

		if ($node->getDefault() !== null) {
			$assignedTypes[] = $scope->getType($node->getDefault());
		}

		if ($node->getNativeType() === null && $node->getDefault() === null) {
			$assignedTypes[] = new NullType();
		}

		if (count($assignedTypes) === 0) {
			return [];
		}

		$assignedType = TypeCombinator::union(...$assignedTypes);

		$unionMessagePattern = '%s (%s) is never assigned %%s so it can be removed from the property type.';
		$boolMessagePattern = '%s (%s) is never assigned %%s so the property type can be changed to %%s.';

		if (!$phpDocPropertyType instanceof MixedType || $phpDocPropertyType->isExplicitMixed()) {
			$phpDocPropertyType = TypeUtils::resolveLateResolvableTypes(TypehintHelper::decideType($nativePropertyType, $phpDocPropertyType));
			$narrowedPhpDocType = $this->narrowType($phpDocPropertyType, $assignedType, $scope, false, false);
			if (!$narrowedPhpDocType->equals($phpDocPropertyType)) {
				$phpDocPropertyTypeDescription = $phpDocPropertyType->describe(VerbosityLevel::getRecommendedLevelByType($phpDocPropertyType));
				return $this->createErrors(
					$narrowedPhpDocType,
					$phpDocPropertyType,
					sprintf($unionMessagePattern, $propertyDescription, $phpDocPropertyTypeDescription),
					sprintf($boolMessagePattern, $propertyDescription, $phpDocPropertyTypeDescription),
					$node->getStartLine(),
					'property',
					null,
				);
			}

			if (!$this->reportNestedTooWideType) {
				return [];
			}

			$narrowedPhpDocType = SimultaneousTypeTraverser::map($phpDocPropertyType, $assignedType, function (Type $declaredType, Type $actualType, callable $traverse) use ($scope) {
				$narrowed = $this->narrowType($declaredType, $actualType, $scope, false, false);
				if (!$narrowed->equals($declaredType)) {
					return $narrowed;
				}
				return $traverse($declaredType, $actualType);
			});
			if ($narrowedPhpDocType->equals($phpDocPropertyType)) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Type %s of %s can be narrowed to %s.',
					$phpDocPropertyType->describe(VerbosityLevel::getRecommendedLevelByType($phpDocPropertyType)),
					lcfirst($propertyDescription),
					$narrowedPhpDocType->describe(VerbosityLevel::getRecommendedLevelByType($narrowedPhpDocType)),
				))->identifier('property.nestedUnusedType')
					->line($node->getStartLine())
					->acceptsReasonsTip($narrowedPhpDocType->accepts($phpDocPropertyType, true)->reasons)
					->build(),
			];
		}

		$narrowedNativeType = $this->narrowType($nativePropertyType, $assignedType, $scope, false, true);
		if (!$narrowedNativeType->equals($nativePropertyType)) {
			$propertyTypeDescription = $nativePropertyType->describe(VerbosityLevel::getRecommendedLevelByType($nativePropertyType));
			return $this->createErrors(
				$narrowedNativeType,
				$nativePropertyType,
				sprintf($unionMessagePattern, $propertyDescription, $propertyTypeDescription),
				sprintf($boolMessagePattern, $propertyDescription, $propertyTypeDescription),
				$node->getStartLine(),
				'property',
				null,
			);
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkFunctionReturnType(
		MethodReturnStatementsNode|FunctionReturnStatementsNode $node,
		Type $nativeFunctionReturnType,
		Type $phpDocFunctionReturnType,
		string $functionDescription,
		bool $checkDescendantClass,
		Scope $scope,
	): array
	{
		$statementResult = $node->getStatementResult();
		if ($statementResult->hasYield()) {
			return [];
		}

		$returnStatements = $node->getReturnStatements();
		if (count($returnStatements) === 0) {
			return [];
		}

		$returnTypes = [];
		foreach ($returnStatements as $returnStatement) {
			$returnNode = $returnStatement->getReturnNode();
			if ($returnNode->expr === null) {
				$returnTypes[] = new VoidType();
				continue;
			}

			$returnTypes[] = $returnStatement->getScope()->getType($returnNode->expr);
		}

		if (!$statementResult->isAlwaysTerminating()) {
			$returnTypes[] = new VoidType();
		}

		if (!$node->hasNativeReturnTypehint()) {
			foreach ($node->getExecutionEnds() as $executionEnd) {
				if ($executionEnd->getStatementResult()->isAlwaysTerminating()) {
					continue;
				}

				$returnTypes[] = new NullType();
				break;
			}
		}

		$returnType = TypeCombinator::union(...$returnTypes);

		if (
			count($returnStatements) === 1
			&& $returnStatements[0]->getReturnNode()->expr instanceof ConstFetch
			&& in_array(strtolower($returnStatements[0]->getReturnNode()->expr->name->toString()), ['true', 'false'], true)
			&& (
				$nativeFunctionReturnType->isBoolean()->yes()
				|| $phpDocFunctionReturnType->isBoolean()->yes()
			)
		) {
			return [];
		}

		$unionMessagePattern = sprintf('%s never returns %%s so it can be removed from the return type.', $functionDescription);
		$boolMessagePattern = sprintf('%s never returns %%s so the return type can be changed to %%s.', $functionDescription);

		// Do not require to have @return null/true/false in descendant classes
		if (
			$checkDescendantClass
			&& ($returnType->isNull()->yes() || $returnType->isTrue()->yes() || $returnType->isFalse()->yes())
		) {
			return [];
		}

		if (!$phpDocFunctionReturnType instanceof MixedType || $phpDocFunctionReturnType->isExplicitMixed()) {
			$phpDocFunctionReturnType = TypeUtils::resolveLateResolvableTypes(TypehintHelper::decideType($nativeFunctionReturnType, $phpDocFunctionReturnType));

			$narrowedPhpDocType = $this->narrowType($phpDocFunctionReturnType, $returnType, $scope, false, false);
			if (!$narrowedPhpDocType->equals($phpDocFunctionReturnType)) {
				return $this->createErrors(
					$narrowedPhpDocType,
					$phpDocFunctionReturnType,
					$unionMessagePattern,
					$boolMessagePattern,
					$node->getStartLine(),
					'return',
					null,
				);
			}

			if (!$this->reportNestedTooWideType) {
				return [];
			}

			$narrowedPhpDocType = SimultaneousTypeTraverser::map($phpDocFunctionReturnType, $returnType, function (Type $declaredType, Type $actualReturnType, callable $traverse) use ($scope, $checkDescendantClass) {
				$narrowed = $this->narrowType($declaredType, $actualReturnType, $scope, $checkDescendantClass, false);
				if (!$narrowed->equals($declaredType)) {
					return $narrowed;
				}
				return $traverse($declaredType, $actualReturnType);
			});
			if ($narrowedPhpDocType->equals($phpDocFunctionReturnType)) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Return type %s of %s can be narrowed to %s.',
					$phpDocFunctionReturnType->describe(VerbosityLevel::getRecommendedLevelByType($phpDocFunctionReturnType)),
					lcfirst($functionDescription),
					$narrowedPhpDocType->describe(VerbosityLevel::getRecommendedLevelByType($narrowedPhpDocType)),
				))->identifier('return.nestedUnusedType')
					->line($node->getStartLine())
					->acceptsReasonsTip($narrowedPhpDocType->accepts($phpDocFunctionReturnType, true)->reasons)
					->build(),
			];
		}

		$narrowedNativeType = $this->narrowType($nativeFunctionReturnType, $returnType, $scope, false, true);
		if (!$narrowedNativeType->equals($nativeFunctionReturnType)) {
			return $this->createErrors(
				$narrowedNativeType,
				$nativeFunctionReturnType,
				$unionMessagePattern,
				$boolMessagePattern,
				$node->getStartLine(),
				'return',
				null,
			);
		}

		return [];
	}

	/**
	 * @param 'paramOut'|'parameterByRef' $identifierPart
	 * @return list<IdentifierRuleError>
	 */
	public function checkParameterOutType(
		Type $parameterOutType,
		Type $actualVariableType,
		string $unionMessagePattern,
		string $boolMessagePattern,
		string $nestedTooWideTypePattern,
		Scope $scope,
		int $startLine,
		string $identifierPart,
		?string $tip,
	): array
	{
		$parameterOutType = TypeUtils::resolveLateResolvableTypes($parameterOutType);
		$narrowedType = $this->narrowType($parameterOutType, $actualVariableType, $scope, false, false);
		if ($narrowedType->equals($parameterOutType)) {
			if (!$this->reportNestedTooWideType) {
				return [];
			}

			$narrowedType = SimultaneousTypeTraverser::map($parameterOutType, $actualVariableType, function (Type $declaredType, Type $actualType, callable $traverse) use ($scope) {
				$narrowed = $this->narrowType($declaredType, $actualType, $scope, false, false);
				if (!$narrowed->equals($declaredType)) {
					return $narrowed;
				}
				return $traverse($declaredType, $actualType);
			});
			if ($narrowedType->equals($parameterOutType)) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					$nestedTooWideTypePattern,
					$parameterOutType->describe(VerbosityLevel::getRecommendedLevelByType($parameterOutType)),
					$narrowedType->describe(VerbosityLevel::getRecommendedLevelByType($narrowedType)),
				))->identifier(sprintf('%s.nestedUnusedType', $identifierPart))
					->line($startLine)
					->acceptsReasonsTip($narrowedType->accepts($parameterOutType, true)->reasons)
					->build(),
			];
		}

		return $this->createErrors(
			$narrowedType,
			$parameterOutType,
			$unionMessagePattern,
			$boolMessagePattern,
			$startLine,
			$identifierPart,
			$tip,
		);
	}

	/**
	 * @param 'return'|'property'|'paramOut'|'parameterByRef' $identifierPart
	 * @return list<IdentifierRuleError>
	 */
	private function createErrors(
		Type $narrowedType,
		Type $originalType,
		string $unionMessagePattern,
		string $boolMessagePattern,
		int $startLine,
		string $identifierPart,
		?string $tip,
	): array
	{
		if ($originalType->isBoolean()->yes()) {
			$neverReturns = $narrowedType->isTrue()->yes() ? new ConstantBooleanType(false) : new ConstantBooleanType(true);

			$errorBuilder = RuleErrorBuilder::message(sprintf(
				$boolMessagePattern,
				$neverReturns->describe(VerbosityLevel::getRecommendedLevelByType($neverReturns)),
				$narrowedType->describe(VerbosityLevel::getRecommendedLevelByType($narrowedType)),
			))->identifier(sprintf('%s.tooWideBool', $identifierPart))->line($startLine);
			if ($tip !== null) {
				$errorBuilder->tip($tip);
			}

			return [$errorBuilder->build()];
		}

		if (!$originalType instanceof UnionType) {
			return [];
		}

		$messages = [];
		foreach ($originalType->getTypes() as $innerType) {
			if (!$narrowedType->isSuperTypeOf($innerType)->no()) {
				continue;
			}

			$errorBuilder = RuleErrorBuilder::message(sprintf(
				$unionMessagePattern,
				$innerType->describe(VerbosityLevel::getRecommendedLevelByType($innerType)),
			))->identifier(sprintf('%s.unusedType', $identifierPart))->line($startLine);
			if ($tip !== null) {
				$errorBuilder->tip($tip);
			}

			$messages[] = $errorBuilder->build();
		}

		return $messages;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkAnonymousFunction(
		Type $returnType,
		UnionType $functionReturnType,
	): array
	{
		if ($returnType->isNull()->yes()) {
			return [];
		}
		$messages = [];
		foreach ($functionReturnType->getTypes() as $type) {
			if (!$type->isSuperTypeOf($returnType)->no()) {
				continue;
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'Anonymous function never returns %s so it can be removed from the return type.',
				$type->describe(VerbosityLevel::getRecommendedLevelByType($type)),
			))->identifier('return.unusedType')->build();
		}

		return $messages;
	}

	private function narrowType(
		Type $declaredType,
		Type $actualReturnType,
		Scope $scope,
		bool $checkDescendantClass,
		bool $native,
	): Type
	{
		if ($declaredType instanceof UnionType) {
			if (
				$declaredType->isConstantScalarValue()->yes()
				&& $actualReturnType->isConstantScalarValue()->yes()
			) {
				return $declaredType;
			}
			$usedTypes = [];
			foreach ($declaredType->getTypes() as $innerType) {
				if ($innerType->isSuperTypeOf($actualReturnType)->no()) {
					if (!$checkDescendantClass) {
						continue;
					}
					if ($innerType->isNull()->yes()) {
						$usedTypes[] = $innerType;
						continue;
					}
					if (
						!$actualReturnType->isTrue()->yes()
						&& !$actualReturnType->isFalse()->yes()
						&& !$actualReturnType->isNull()->yes()
					) {
						continue;
					}
				}

				$usedTypes[] = $innerType;
			}

			return TypeCombinator::union(...$usedTypes);
		}

		if (!$this->reportTooWideBool) {
			return $declaredType;
		}

		if ($native && !$scope->getPhpVersion()->supportsTrueAndFalseStandaloneType()->yes()) {
			return $declaredType;
		}

		if (!$declaredType->isBoolean()->yes()) {
			return $declaredType;
		}

		if (
			$declaredType->isTrue()->yes()
			|| $declaredType->isFalse()->yes()
		) {
			return $declaredType;
		}

		$usedTypes = [];
		foreach ($declaredType->getFiniteTypes() as $innerType) {
			if ($innerType->isSuperTypeOf($actualReturnType)->no()) {
				if (!$checkDescendantClass) {
					continue;
				}
				if (
					!$actualReturnType->isTrue()->yes()
					&& !$actualReturnType->isFalse()->yes()
					&& !$actualReturnType->isNull()->yes()
				) {
					continue;
				}
			}

			$usedTypes[] = $innerType;
		}

		return TypeCombinator::union(...$usedTypes);
	}

}

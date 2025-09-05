<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use function count;
use function sprintf;

#[AutowiredService]
final class TooWideTypeCheck
{

	public function __construct(
		#[AutowiredParameter(ref: '%featureToggles.reportTooWideBool%')]
		private bool $reportTooWideBool,
	)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkProperty(
		ClassPropertyNode $property,
		Type $propertyType,
		string $propertyDescription,
		Type $assignedType,
	): array
	{
		$errors = [];

		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($propertyType, $assignedType);
		$propertyTypes = $propertyType instanceof UnionType ? $propertyType->getTypes() : $propertyType->getFiniteTypes();
		foreach ($propertyTypes as $type) {
			if (!$type->isSuperTypeOf($assignedType)->no()) {
				continue;
			}

			if ($property->getNativeType() === null && $type->isNull()->yes()) {
				continue;
			}

			if ($propertyType->isBoolean()->yes()) {
				$suggestedType = $type->isTrue()->yes() ? new ConstantBooleanType(false) : new ConstantBooleanType(true);

				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s (%s) is never assigned %s so the property type can be changed to %s.',
					$propertyDescription,
					$propertyType->describe($verbosityLevel),
					$type->describe($verbosityLevel),
					$suggestedType->describe($verbosityLevel),
				))
					->identifier('property.tooWideBool')
					->line($property->getStartLine())
					->build();

				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s (%s) is never assigned %s so it can be removed from the property type.',
				$propertyDescription,
				$propertyType->describe($verbosityLevel),
				$type->describe($verbosityLevel),
			))
				->identifier('property.unusedType')
				->line($property->getStartLine())
				->build();
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkFunction(
		MethodReturnStatementsNode|FunctionReturnStatementsNode $node,
		Type $nativeFunctionReturnType,
		Type $phpdocFunctionReturnType,
		string $functionDescription,
		bool $checkDescendantClass,
		Scope $scope,
	): array
	{
		$functionReturnType = $this->findTypeToCheck($nativeFunctionReturnType, $phpdocFunctionReturnType, $scope);
		if ($functionReturnType === null) {
			return [];
		}

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

		$returnType = TypeCombinator::union(...$returnTypes);

		if (
			$returnType->isConstantScalarValue()->yes()
			&& $functionReturnType->isConstantScalarValue()->yes()
		) {
			return [];
		}

		// Do not require to have @return null/true/false in descendant classes
		if (
			$checkDescendantClass
			&& ($returnType->isNull()->yes() || $returnType->isTrue()->yes() || $returnType->isFalse()->yes())
		) {
			return [];
		}

		$messages = [];
		$functionReturnTypes = $functionReturnType instanceof UnionType ? $functionReturnType->getTypes() : $functionReturnType->getFiniteTypes();
		foreach ($functionReturnTypes as $type) {
			if (!$type->isSuperTypeOf($returnType)->no()) {
				continue;
			}

			if ($type->isNull()->yes() && !$node->hasNativeReturnTypehint()) {
				foreach ($node->getExecutionEnds() as $executionEnd) {
					if ($executionEnd->getStatementResult()->isAlwaysTerminating()) {
						continue;
					}

					continue 2;
				}
			}

			if ($functionReturnType->isBoolean()->yes()) {
				$suggestedType = $type->isTrue()->yes() ? new ConstantBooleanType(false) : new ConstantBooleanType(true);

				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s never returns %s so the return type can be changed to %s.',
					$functionDescription,
					$type->describe(VerbosityLevel::getRecommendedLevelByType($type)),
					$suggestedType->describe(VerbosityLevel::getRecommendedLevelByType($suggestedType)),
				))->identifier('return.tooWideBool')->build();

				continue;
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s never returns %s so it can be removed from the return type.',
				$functionDescription,
				$type->describe(VerbosityLevel::getRecommendedLevelByType($type)),
			))->identifier('return.unusedType')->build();
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

	/**
	 * Returns null when type should not be checked, e.g. because it would be too annoying.
	 */
	public function findTypeToCheck(
		Type $nativeType,
		Type $phpdocType,
		Scope $scope,
	): ?Type
	{
		$combinedType = TypeUtils::resolveLateResolvableTypes(TypehintHelper::decideType($nativeType, $phpdocType));
		if ($combinedType instanceof UnionType) {
			return $combinedType;
		}

		if (!$this->reportTooWideBool) {
			return null;
		}

		if (
			$phpdocType->isBoolean()->yes()
		) {
			if (
				!$phpdocType->isTrue()->yes()
				&& !$phpdocType->isFalse()->yes()
			) {
				return $combinedType;
			}
		} elseif (
			$scope->getPhpVersion()->supportsTrueAndFalseStandaloneType()->yes()
			&& $nativeType->isBoolean()->yes()
		) {
			if (
				!$nativeType->isTrue()->yes()
				&& !$nativeType->isFalse()->yes()
			) {
				return $combinedType;
			}
		}

		return null;
	}

}

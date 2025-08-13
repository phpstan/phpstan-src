<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use function count;
use function sprintf;

#[AutowiredService]
final class TooWideTypeCheck
{

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkProperty(
		ClassPropertyNode $property,
		UnionType $propertyType,
		string $propertyDescription,
		Type $assignedType,
	): array
	{
		$errors = [];

		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($propertyType, $assignedType);
		foreach ($propertyType->getTypes() as $type) {
			if (!$type->isSuperTypeOf($assignedType)->no()) {
				continue;
			}

			if ($property->getNativeType() === null && $type->isNull()->yes()) {
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
		Type $functionReturnType,
		string $functionDescription,
		bool $checkDescendantClass,
	): array
	{
		$functionReturnType = TypeUtils::resolveLateResolvableTypes($functionReturnType);
		if (!$functionReturnType instanceof UnionType) {
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
		foreach ($functionReturnType->getTypes() as $type) {
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

}

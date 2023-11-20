<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node\Expr;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class NonexistentOffsetInArrayDimFetchCheck
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private bool $reportMaybes,
		private bool $bleedingEdge,
	)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		Scope $scope,
		Expr $var,
		string $unknownClassPattern,
		Type $dimType,
	): array
	{
		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $var),
			$unknownClassPattern,
			static fn (Type $type): bool => $type->hasOffsetValueType($dimType)->yes(),
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}

		if ($scope->isInExpressionAssign($var) || $scope->isUndefinedExpressionAllowed($var)) {
			return [];
		}

		if ($type->hasOffsetValueType($dimType)->no()) {
			return [
				RuleErrorBuilder::message(sprintf('Offset %s does not exist on %s.', $dimType->describe(VerbosityLevel::value()), $type->describe(VerbosityLevel::value())))
					->identifier('offsetAccess.notFound')
					->build(),
			];
		}

		if ($this->reportMaybes) {
			$report = false;

			if ($type instanceof BenevolentUnionType) {
				$flattenedTypes = [$type];
			} else {
				$flattenedTypes = TypeUtils::flattenTypes($type);
			}
			foreach ($flattenedTypes as $innerType) {
				if ($dimType instanceof UnionType) {
					if ($innerType->hasOffsetValueType($dimType)->no()) {
						$report = true;
						break;
					}
					continue;
				}
				foreach (TypeUtils::flattenTypes($dimType) as $innerDimType) {
					if ($innerType->hasOffsetValueType($innerDimType)->no()) {
						$report = true;
						break 2;
					}
				}
			}

			if ($report) {
				if ($this->bleedingEdge) {
					return [
						RuleErrorBuilder::message(sprintf('Offset %s might not exist on %s.', $dimType->describe(VerbosityLevel::value()), $type->describe(VerbosityLevel::value())))
							->identifier('offsetAccess.notFound')
							->build(),
					];
				}
				return [
					RuleErrorBuilder::message(sprintf('Offset %s does not exist on %s.', $dimType->describe(VerbosityLevel::value()), $type->describe(VerbosityLevel::value())))
						->identifier('offsetAccess.notFound')
						->build(),
				];
			}
		}

		return [];
	}

}

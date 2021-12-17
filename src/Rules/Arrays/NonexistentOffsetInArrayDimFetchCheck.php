<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node\Expr;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

class NonexistentOffsetInArrayDimFetchCheck
{

	private RuleLevelHelper $ruleLevelHelper;

	private bool $reportMaybes;

	public function __construct(RuleLevelHelper $ruleLevelHelper, bool $reportMaybes)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->reportMaybes = $reportMaybes;
	}

	/**
	 * @return RuleError[]
	 */
	public function check(
		Scope $scope,
		Expr $var,
		string $unknownClassPattern,
		Type $dimType
	): array
	{
		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $var),
			$unknownClassPattern,
			static function (Type $type) use ($dimType): bool {
				return $type->hasOffsetValueType($dimType)->yes();
			},
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}

		$hasOffsetValueType = $type->hasOffsetValueType($dimType);
		$report = $hasOffsetValueType->no();
		if ($hasOffsetValueType->maybe()) {
			$constantArrays = TypeUtils::getOldConstantArrays($type);
			if (count($constantArrays) > 0) {
				foreach ($constantArrays as $constantArray) {
					if ($constantArray->hasOffsetValueType($dimType)->no()) {
						$report = true;
						break;
					}
				}
			}
		}

		if (!$report && $this->reportMaybes) {
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
						break;
					}
				}
			}
		}

		if ($report) {
			if ($scope->isInExpressionAssign($var)) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf('Offset %s does not exist on %s.', $dimType->describe(VerbosityLevel::value()), $type->describe(VerbosityLevel::value())))->build(),
			];
		}

		return [];
	}

}

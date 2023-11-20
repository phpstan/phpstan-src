<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\ArrayDimFetch>
 */
class OffsetAccessAssignmentRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\ArrayDimFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInExpressionAssign($node)) {
			return [];
		}

		$potentialDimType = null;
		if ($node->dim !== null) {
			$potentialDimType = $scope->getType($node->dim);
		}

		$varTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $node->var),
			'',
			static function (Type $varType) use ($potentialDimType): bool {
				$arrayDimType = $varType->setOffsetValueType($potentialDimType, new MixedType());
				return !($arrayDimType instanceof ErrorType);
			},
		);
		$varType = $varTypeResult->getType();
		if ($varType instanceof ErrorType) {
			return [];
		}
		if (!$varType->isOffsetAccessible()->yes()) {
			return [];
		}

		if ($node->dim !== null) {
			$dimTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$node->dim,
				'',
				static function (Type $dimType) use ($varType): bool {
					$arrayDimType = $varType->setOffsetValueType($dimType, new MixedType());
					return !($arrayDimType instanceof ErrorType);
				},
			);
			$dimType = $dimTypeResult->getType();
		} else {
			$dimType = $potentialDimType;
		}

		$resultType = $varType->setOffsetValueType($dimType, new MixedType());
		if (!($resultType instanceof ErrorType)) {
			return [];
		}

		if ($dimType === null) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot assign new offset to %s.',
					$varType->describe(VerbosityLevel::typeOnly()),
				))->identifier('offsetAssign.dimType')->build(),
			];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Cannot assign offset %s to %s.',
				$dimType->describe(VerbosityLevel::value()),
				$varType->describe(VerbosityLevel::typeOnly()),
			))->identifier('offsetAssign.dimType')->build(),
		];
	}

}

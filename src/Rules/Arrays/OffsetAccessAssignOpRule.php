<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
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
 * @implements Rule<Node\Expr\AssignOp>
 */
class OffsetAccessAssignOpRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\AssignOp::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->var instanceof ArrayDimFetch) {
			return [];
		}

		$arrayDimFetch = $node->var;

		$potentialDimType = null;
		if ($arrayDimFetch->dim !== null) {
			$potentialDimType = $scope->getType($arrayDimFetch->dim);
		}

		$varTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$arrayDimFetch->var,
			'',
			static function (Type $varType) use ($potentialDimType): bool {
				$arrayDimType = $varType->setOffsetValueType($potentialDimType, new MixedType());
				return !($arrayDimType instanceof ErrorType);
			},
		);
		$varType = $varTypeResult->getType();

		if ($arrayDimFetch->dim !== null) {
			$dimTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$arrayDimFetch->dim,
				'',
				static function (Type $dimType) use ($varType): bool {
					$arrayDimType = $varType->setOffsetValueType($dimType, new MixedType());
					return !($arrayDimType instanceof ErrorType);
				},
			);
			$dimType = $dimTypeResult->getType();
			if ($varType->hasOffsetValueType($dimType)->no()) {
				return [];
			}
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

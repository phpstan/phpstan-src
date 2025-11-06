<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Expr>
 */
#[RegisteredRule(level: 3)]
final class OffsetAccessValueAssignmentRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Assign
			&& !$node instanceof AssignOp
			&& !$node instanceof Expr\AssignRef
		) {
			return [];
		}

		if (!$node->var instanceof Expr\ArrayDimFetch) {
			return [];
		}

		$arrayDimFetch = $node->var;
		$varType = $scope->getType($arrayDimFetch->var);
		if ($varType->isObject()->no()) {
			return [];
		}

		if ($node instanceof Assign || $node instanceof Expr\AssignRef) {
			$assignedValueType = $scope->getType($node->expr);
		} else {
			$assignedValueType = $scope->getType($node);
		}

		$arrayTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$arrayDimFetch->var,
			'',
			static function (Type $varType) use ($assignedValueType): bool {
				$result = $varType->setOffsetValueType(new MixedType(), $assignedValueType);
				return !($result->isError()->yes());
			},
		);
		$arrayType = $arrayTypeResult->getType();
		if ($arrayType->isError()->yes()) {
			return [];
		}
		$isOffsetAccessible = $arrayType->isOffsetAccessible();
		if (!$isOffsetAccessible->yes()) {
			return [];
		}
		$resultType = $arrayType->setOffsetValueType(new MixedType(), $assignedValueType);
		if (!$resultType->isError()->yes()) {
			return [];
		}

		$originalArrayType = $scope->getType($arrayDimFetch->var);

		return [
			RuleErrorBuilder::message(sprintf(
				'%s does not accept %s.',
				$originalArrayType->describe(VerbosityLevel::value()),
				$assignedValueType->describe(VerbosityLevel::typeOnly()),
			))->identifier('offsetAssign.valueType')->build(),
		];
	}

}

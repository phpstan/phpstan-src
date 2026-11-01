<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\MultipleNodeTypesRule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements MultipleNodeTypesRule<Expr>
 */
#[RegisteredRule(level: 3)]
final class OffsetAccessValueAssignmentRule implements MultipleNodeTypesRule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeTypes(): array
	{
		return [Assign::class, AssignOp::class, Expr\AssignRef::class];
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

		// The offset type lets method-level template types of offsetSet() (e.g. `@param T[TOffset] $value`)
		// resolve against the written key. An offset that is not accepted is reported by OffsetAccessAssignmentRule,
		// so only the value is checked here.
		$dimType = $arrayDimFetch->dim !== null ? $scope->getType($arrayDimFetch->dim) : null;
		$offsetTypeFor = static function (Type $varType) use ($dimType): Type {
			if ($dimType === null || $varType->setOffsetValueType($dimType, new MixedType()) instanceof ErrorType) {
				return new MixedType();
			}

			return $dimType;
		};

		$arrayTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$arrayDimFetch->var,
			'',
			static function (Type $varType) use ($assignedValueType, $offsetTypeFor): bool {
				$result = $varType->setOffsetValueType($offsetTypeFor($varType), $assignedValueType);
				return !($result instanceof ErrorType);
			},
		);
		$arrayType = $arrayTypeResult->getType();
		if ($arrayType instanceof ErrorType) {
			return [];
		}
		$isOffsetAccessible = $arrayType->isOffsetAccessible();
		if (!$isOffsetAccessible->yes()) {
			return [];
		}
		$resultType = $arrayType->setOffsetValueType($offsetTypeFor($arrayType), $assignedValueType);
		if (!$resultType instanceof ErrorType) {
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

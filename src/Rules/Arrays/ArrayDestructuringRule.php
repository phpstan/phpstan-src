<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Scalar\LNumber;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<Assign>
 */
class ArrayDestructuringRule implements Rule
{

	private RuleLevelHelper $ruleLevelHelper;

	private NonexistentOffsetInArrayDimFetchCheck $nonexistentOffsetInArrayDimFetchCheck;

	public function __construct(
		RuleLevelHelper $ruleLevelHelper,
		NonexistentOffsetInArrayDimFetchCheck $nonexistentOffsetInArrayDimFetchCheck
	)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->nonexistentOffsetInArrayDimFetchCheck = $nonexistentOffsetInArrayDimFetchCheck;
	}

	public function getNodeType(): string
	{
		return Assign::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->var instanceof Node\Expr\List_ && !$node->var instanceof Node\Expr\Array_) {
			return [];
		}

		return $this->getErrors(
			$scope,
			$node->var,
			$node->expr
		);
	}

	/**
	 * @param Node\Expr\List_|Node\Expr\Array_ $var
	 * @return RuleError[]
	 */
	private function getErrors(Scope $scope, Expr $var, Expr $expr): array
	{
		$exprTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$expr,
			'',
			static function (Type $varType): bool {
				return $varType->isArray()->yes();
			},
			false
		);
		$exprType = $exprTypeResult->getType();
		if ($exprType instanceof ErrorType) {
			return [];
		}
		if (!$exprType->isArray()->yes()) {
			return [
				RuleErrorBuilder::message(sprintf('Cannot use array destructuring on %s.', $exprType->describe(VerbosityLevel::typeOnly())))->build(),
			];
		}

		$errors = [];
		$i = 0;
		foreach ($var->items as $item) {
			if ($item === null) {
				$i++;
				continue;
			}

			$keyExpr = null;
			if ($item->key === null) {
				$keyType = new ConstantIntegerType($i);
				$keyExpr = new Node\Scalar\LNumber($i);
			} else {
				$keyType = $scope->getType($item->key);
				if ($keyType instanceof ConstantIntegerType) {
					$keyExpr = new LNumber($keyType->getValue());
				} elseif ($keyType instanceof ConstantStringType) {
					$keyExpr = new Node\Scalar\String_($keyType->getValue());
				}
			}

			$itemErrors = $this->nonexistentOffsetInArrayDimFetchCheck->check(
				$scope,
				$expr,
				'',
				$keyType
			);
			$errors = array_merge($errors, $itemErrors);

			if ($keyExpr === null) {
				$i++;
				continue;
			}

			if (!$item->value instanceof Node\Expr\List_ && !$item->value instanceof Node\Expr\Array_) {
				$i++;
				continue;
			}

			$errors = array_merge($errors, $this->getErrors(
				$scope,
				$item->value,
				new Expr\ArrayDimFetch($expr, $keyExpr)
			));
		}

		return $errors;
	}

}

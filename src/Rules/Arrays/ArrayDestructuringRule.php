<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use ArrayAccess;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<Assign>
 */
class ArrayDestructuringRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private NonexistentOffsetInArrayDimFetchCheck $nonexistentOffsetInArrayDimFetchCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return Assign::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->var instanceof Node\Expr\List_) {
			return [];
		}

		return $this->getErrors(
			$scope,
			$node->var,
			$node->expr,
		);
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function getErrors(Scope $scope, Node\Expr\List_ $var, Expr $expr): array
	{
		$exprTypeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$expr,
			'',
			static fn (Type $varType): bool => $varType->isArray()->yes() || (new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->yes(),
		);
		$exprType = $exprTypeResult->getType();
		if ($exprType instanceof ErrorType) {
			return [];
		}
		if (!$exprType->isArray()->yes() && !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($exprType)->yes()) {
			return [
				RuleErrorBuilder::message(sprintf('Cannot use array destructuring on %s.', $exprType->describe(VerbosityLevel::typeOnly())))
					->identifier('offsetAccess.nonArray')
					->build(),
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
				$keyExpr = new Node\Scalar\Int_($i);
			} else {
				$keyType = $scope->getType($item->key);
				$keyExpr = new TypeExpr($keyType);
			}

			$itemErrors = $this->nonexistentOffsetInArrayDimFetchCheck->check(
				$scope,
				$expr,
				'',
				$keyType,
			);
			$errors = array_merge($errors, $itemErrors);

			if (!$item->value instanceof Node\Expr\List_) {
				$i++;
				continue;
			}

			$errors = array_merge($errors, $this->getErrors(
				$scope,
				$item->value,
				new Expr\ArrayDimFetch($expr, $keyExpr),
			));
		}

		return $errors;
	}

}

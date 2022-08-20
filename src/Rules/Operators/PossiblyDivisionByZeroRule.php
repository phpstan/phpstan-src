<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr>
 */
class PossiblyDivisionByZeroRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private bool $checkNullables,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\BinaryOp\Div::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof Node\Expr\BinaryOp\Div) {
			return [];
		}

		$left = $node->left;
		$right = $node->right;

		$checkNullables = $this->checkNullables;
		$callback = static fn (Type $type): bool => (new ConstantIntegerType(0))->isSuperTypeOf($type)->no()
			&& (!$checkNullables || (new NullType())->isSuperTypeOf($type)->no());

		$rightType = $this->ruleLevelHelper->findTypeToCheck($scope, $right, '', $callback)->getType();

		if (
			!$rightType instanceof MixedType
			&& (
				!(new ConstantIntegerType(0))->isSuperTypeOf($rightType)->no()
				|| $checkNullables && !(new NullType())->isSuperTypeOf($rightType)->no()
			)
		) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Division by "%s" might result in a division by zero.',
					$scope->getType($right)->describe(VerbosityLevel::value()),
				))->line($left->getLine())->build(),
			];
		}

		return [];
	}

}

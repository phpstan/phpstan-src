<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
use function strlen;
use function substr;

/**
 * @implements Rule<Node\Expr>
 */
class InvalidBinaryOperationRule implements Rule
{

	private Standard $printer;

	private RuleLevelHelper $ruleLevelHelper;

	public function __construct(
		Standard $printer,
		RuleLevelHelper $ruleLevelHelper
	)
	{
		$this->printer = $printer;
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Expr\BinaryOp
			&& !$node instanceof Node\Expr\AssignOp
		) {
			return [];
		}

		if ($scope->getType($node) instanceof ErrorType) {
			$leftName = '__PHPSTAN__LEFT__';
			$rightName = '__PHPSTAN__RIGHT__';
			$leftVariable = new Node\Expr\Variable($leftName);
			$rightVariable = new Node\Expr\Variable($rightName);
			if ($node instanceof Node\Expr\AssignOp) {
				$newNode = clone $node;
				$left = $node->var;
				$right = $node->expr;
				$newNode->var = $leftVariable;
				$newNode->expr = $rightVariable;
			} else {
				$newNode = clone $node;
				$left = $node->left;
				$right = $node->right;
				$newNode->left = $leftVariable;
				$newNode->right = $rightVariable;
			}

			if ($node instanceof Node\Expr\AssignOp\Concat || $node instanceof Node\Expr\BinaryOp\Concat) {
				$callback = static function (Type $type): bool {
					return !$type->toString() instanceof ErrorType;
				};
			} else {
				$callback = static function (Type $type): bool {
					return !$type->toNumber() instanceof ErrorType;
				};
			}

			$leftType = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$left,
				'',
				$callback
			)->getType();
			if ($leftType instanceof ErrorType) {
				return [];
			}

			$rightType = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$right,
				'',
				$callback
			)->getType();
			if ($rightType instanceof ErrorType) {
				return [];
			}

			if (!$scope instanceof MutatingScope) {
				throw new ShouldNotHappenException();
			}

			$scope = $scope
				->assignVariable($leftName, $leftType)
				->assignVariable($rightName, $rightType);

			if (!$scope->getType($newNode) instanceof ErrorType) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Binary operation "%s" between %s and %s results in an error.',
					substr(substr($this->printer->prettyPrintExpr($newNode), strlen($leftName) + 2), 0, -(strlen($rightName) + 2)),
					$scope->getType($left)->describe(VerbosityLevel::value()),
					$scope->getType($right)->describe(VerbosityLevel::value())
				))->line($left->getLine())->build(),
			];
		}

		return [];
	}

}

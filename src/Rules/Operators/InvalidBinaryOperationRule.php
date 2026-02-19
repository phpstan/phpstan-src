<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
use function strlen;
use function strpos;
use function substr;

/**
 * @implements Rule<Node\Expr>
 */
#[RegisteredRule(level: 2)]
final class InvalidBinaryOperationRule implements Rule
{

	public function __construct(
		private ExprPrinter $exprPrinter,
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
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

		if ($node instanceof Node\Expr\AssignOp) {
			$identifier = 'assignOp';
			$left = $node->var;
			$right = $node->expr;
		} else {
			$identifier = 'binaryOp';
			$left = $node->left;
			$right = $node->right;
		}

		if ($node instanceof Node\Expr\AssignOp\Concat || $node instanceof Node\Expr\BinaryOp\Concat) {
			$callback = static fn (Type $type): bool => !$type->toString() instanceof ErrorType;
		} elseif ($node instanceof Node\Expr\AssignOp\Plus || $node instanceof Node\Expr\BinaryOp\Plus) {
			$callback = static fn (Type $type): bool => !$type->toNumber() instanceof ErrorType || $type->isArray()->yes();
		} else {
			$callback = static fn (Type $type): bool => !$type->toNumber() instanceof ErrorType;
		}

		$leftType = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$left,
			'',
			$callback,
		)->getType();
		if ($leftType instanceof ErrorType) {
			return [];
		}

		$rightType = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$right,
			'',
			$callback,
		)->getType();
		if ($rightType instanceof ErrorType) {
			return [];
		}

		if ($node instanceof Node\Expr\AssignOp) {
			$newNode = clone $node;
			$newNode->setAttribute('phpstan_cache_printer', null);
			$newNode->var = new TypeExpr($leftType);
			$newNode->expr = new TypeExpr($rightType);
			$newLeft = $newNode->var;
			$newRight = $newNode->expr;
		} else {
			$newNode = clone $node;
			$newNode->setAttribute('phpstan_cache_printer', null);
			$newNode->left = new TypeExpr($leftType);
			$newNode->right = new TypeExpr($rightType);
			$newLeft = $newNode->left;
			$newRight = $newNode->right;
		}

		if (!$scope->getType($newNode) instanceof ErrorType) {
			return [];
		}

		$leftPrinted = $this->exprPrinter->printExpr($newLeft);
		$rightPrinted = $this->exprPrinter->printExpr($newRight);

		$opLeftSideTrimmed = substr($this->exprPrinter->printExpr($newNode), strlen($leftPrinted) + 1);
		$pos = strpos($opLeftSideTrimmed, $rightPrinted);
		if ($pos === false) {
			throw new ShouldNotHappenException();
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Binary operation "%s" between %s and %s results in an error.',
				substr($opLeftSideTrimmed, 0, $pos - 1),
				$scope->getType($left)->describe(VerbosityLevel::value()),
				$scope->getType($right)->describe(VerbosityLevel::value()),
			))
				->line($left->getStartLine())
				->identifier(sprintf('%s.invalid', $identifier))
				->build(),
		];
	}

}

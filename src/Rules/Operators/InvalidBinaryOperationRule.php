<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use DivisionByZeroError;
use PhpParser\Node;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\TryCatchTypeVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function sprintf;
use function strlen;
use function substr;

/**
 * @implements Rule<Node\Expr>
 */
final class InvalidBinaryOperationRule implements Rule
{

	public function __construct(
		private ExprPrinter $exprPrinter,
		private RuleLevelHelper $ruleLevelHelper,
		private bool $bleedingEdge,
		private bool $reportMaybes,
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

		if (!$scope->getType($node) instanceof ErrorType && !$this->bleedingEdge) {
			return [];
		}

		$leftName = '__PHPSTAN__LEFT__';
		$rightName = '__PHPSTAN__RIGHT__';
		$leftVariable = new Node\Expr\Variable($leftName);
		$rightVariable = new Node\Expr\Variable($rightName);
		if ($node instanceof Node\Expr\AssignOp) {
			$identifier = 'assignOp';
			$newNode = clone $node;
			$newNode->setAttribute('phpstan_cache_printer', null);
			$left = $node->var;
			$right = $node->expr;
			$newNode->var = $leftVariable;
			$newNode->expr = $rightVariable;
		} else {
			$identifier = 'binaryOp';
			$newNode = clone $node;
			$newNode->setAttribute('phpstan_cache_printer', null);
			$left = $node->left;
			$right = $node->right;
			$newNode->left = $leftVariable;
			$newNode->right = $rightVariable;
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

		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		$scope = $scope
			->assignVariable($leftName, $leftType, $leftType)
			->assignVariable($rightName, $rightType, $rightType);

		if (!$scope->getType($newNode) instanceof ErrorType) {
			if (
				$this->reportMaybes
				&& (
					$node instanceof Node\Expr\AssignOp\Div
					|| $node instanceof Node\Expr\AssignOp\Mod
					|| $node instanceof Node\Expr\BinaryOp\Div
					|| $node instanceof Node\Expr\BinaryOp\Mod
				)
				&& !$this->isDivisionByZeroCaught($node)
				&& !$this->hasDivisionByZeroThrowsTag($scope)
			) {
				$rightType = $scope->getType($right);
				// as long as we don't support float-ranges, we prevent false positives for maybe floats
				if ($rightType instanceof BenevolentUnionType && $rightType->isFloat()->maybe()) {
					return [];
				}

				$zeroType = new ConstantIntegerType(0);
				if ($zeroType->isSuperTypeOf($rightType)->maybe()) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Binary operation "%s" between %s and %s might result in an error.',
							substr(substr($this->exprPrinter->printExpr($newNode), strlen($leftName) + 2), 0, -(strlen($rightName) + 2)),
							$scope->getType($left)->describe(VerbosityLevel::value()),
							$scope->getType($right)->describe(VerbosityLevel::value()),
						))
							->line($left->getStartLine())
							->identifier(sprintf('%s.invalid', $identifier))
							->build(),
					];
				}
			}

			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Binary operation "%s" between %s and %s results in an error.',
				substr(substr($this->exprPrinter->printExpr($newNode), strlen($leftName) + 2), 0, -(strlen($rightName) + 2)),
				$scope->getType($left)->describe(VerbosityLevel::value()),
				$scope->getType($right)->describe(VerbosityLevel::value()),
			))
				->line($left->getStartLine())
				->identifier(sprintf('%s.invalid', $identifier))
				->build(),
		];
	}

	private function isDivisionByZeroCaught(Node $node): bool
	{
		$tryCatchTypes = $node->getAttribute(TryCatchTypeVisitor::ATTRIBUTE_NAME);
		if ($tryCatchTypes === null) {
			return false;
		}

		$tryCatchType = TypeCombinator::union(...array_map(static fn (string $class) => new ObjectType($class), $tryCatchTypes));

		return $tryCatchType->isSuperTypeOf(new ObjectType(DivisionByZeroError::class))->yes();
	}

	private function hasDivisionByZeroThrowsTag(Scope $scope): bool
	{
		$function = $scope->getFunction();
		if ($function === null) {
			return false;
		}

		$throwsType = $function->getThrowType();
		if ($throwsType === null) {
			return false;
		}

		return $throwsType->isSuperTypeOf(new ObjectType(DivisionByZeroError::class))->yes();
	}

}

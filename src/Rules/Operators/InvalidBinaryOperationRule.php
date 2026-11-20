<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MultipleNodeTypesRule;
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
 * @implements MultipleNodeTypesRule<Node\Expr>
 */
#[RegisteredRule(level: 2)]
final class InvalidBinaryOperationRule implements MultipleNodeTypesRule
{

	public function __construct(
		private ExprPrinter $exprPrinter,
		private RuleLevelHelper $ruleLevelHelper,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeTypes(): array
	{
		return [Node\Expr\BinaryOp::class, Node\Expr\AssignOp::class];
	}

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = $this->checkInvalidOperation($node, $scope);
		if ($errors !== [] || !$node instanceof Node\Expr\BinaryOp\Mod || $scope->getType($node) instanceof ErrorType) {
			return $errors;
		}

		return $this->checkModImplicitConversion($node, $scope);
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function checkInvalidOperation(Node $node, Scope $scope): array
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

		// on the very operand types the walk saw, the operation's own type is the
		// answer - the walk already priced it
		if (
			$leftType->equals($scope->getType($left))
			&& $rightType->equals($scope->getType($right))
			&& !$scope->getType($node) instanceof ErrorType
		) {
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

		// getScopeType(): the node was synthesized right here, so NodeScopeResolver
		// never visits it and there is no before-scope to wait for
		if (!$scope->getScopeType($newNode) instanceof ErrorType) {
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

	/**
	 * `%` converts both operands to int: a float operand loses precision,
	 * which PHP 8.1+ reports as deprecated.
	 *
	 * @return list<IdentifierRuleError>
	 */
	private function checkModImplicitConversion(Node\Expr\BinaryOp\Mod $node, Scope $scope): array
	{
		if (!$this->phpVersion->deprecatesImplicitlyFloatConversionToInt()) {
			return [];
		}

		$callback = static fn (Type $type): bool => !$type->isFloat()->no();
		foreach ([$node->left, $node->right] as $operand) {
			$type = $this->ruleLevelHelper->findTypeToCheck($scope, $operand, '', $callback)->getType();
			if ($type instanceof ErrorType || $type->toNumber()->isFloat()->no()) {
				continue;
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Deprecated in PHP 8.1: Implicit conversion from %s to int loses precision.',
					$type->describe(VerbosityLevel::value()),
				))
					->line($node->left->getStartLine())
					->identifier('binaryOp.mod.implicitConversion')
					->build(),
			];
		}

		return [];
	}

}

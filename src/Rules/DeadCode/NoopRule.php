<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Expression>
 */
class NoopRule implements Rule
{

	public function __construct(private ExprPrinter $exprPrinter, private bool $logicalXor)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Expression::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$originalExpr = $node->expr;
		$expr = $originalExpr;
		if (
			$expr instanceof Node\Expr\Cast
			|| $expr instanceof Node\Expr\UnaryMinus
			|| $expr instanceof Node\Expr\UnaryPlus
			|| $expr instanceof Node\Expr\ErrorSuppress
		) {
			$expr = $expr->expr;
		}
		if ($this->logicalXor) {
			if ($expr instanceof Node\Expr\BinaryOp\LogicalXor) {
				return [
					RuleErrorBuilder::message(
						'Unused result of "xor" operator.',
					)->line($expr->getStartLine())
						->tip('This operator has unexpected precedence, try disambiguating the logic with parentheses ().')
						->identifier('logicalXor.resultUnused')
						->build(),
				];
			}
			if ($expr instanceof Node\Expr\BinaryOp\LogicalAnd || $expr instanceof Node\Expr\BinaryOp\LogicalOr) {
				if (!$this->isNoopExpr($expr->right)) {
					return [];
				}

				$identifierType = $expr instanceof Node\Expr\BinaryOp\LogicalAnd ? 'logicalAnd' : 'logicalOr';

				return [
					RuleErrorBuilder::message(sprintf(
						'Unused result of "%s" operator.',
						$expr->getOperatorSigil(),
					))->line($expr->getStartLine())
						->tip('This operator has unexpected precedence, try disambiguating the logic with parentheses ().')
						->identifier(sprintf('%s.resultUnused', $identifierType))
						->build(),
				];
			}

			if ($expr instanceof Node\Expr\BinaryOp\BooleanAnd || $expr instanceof Node\Expr\BinaryOp\BooleanOr) {
				if (!$this->isNoopExpr($expr->right)) {
					return [];
				}

				$identifierType = $expr instanceof Node\Expr\BinaryOp\BooleanAnd ? 'booleanAnd' : 'booleanOr';

				return [
					RuleErrorBuilder::message(sprintf(
						'Unused result of "%s" operator.',
						$expr->getOperatorSigil(),
					))->line($expr->getStartLine())
						->identifier(sprintf('%s.resultUnused', $identifierType))
						->build(),
				];
			}

			if ($expr instanceof Node\Expr\Ternary) {
				$if = $expr->if;
				if ($if === null) {
					$if = $expr->cond;
				}

				if (!$this->isNoopExpr($if) || !$this->isNoopExpr($expr->else)) {
					return [];
				}

				return [
					RuleErrorBuilder::message('Unused result of ternary operator.')
						->line($expr->getStartLine())
						->identifier('ternary.resultUnused')
						->build(),
				];
			}
		}
		if (!$this->isNoopExpr($expr)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Expression "%s" on a separate line does not do anything.',
				$this->exprPrinter->printExpr($originalExpr),
			))->line($expr->getStartLine())
				->identifier('expr.resultUnused')
				->build(),
		];
	}

	public function isNoopExpr(Node\Expr $expr): bool
	{
		return $expr instanceof Node\Expr\Variable
			|| $expr instanceof Node\Expr\PropertyFetch
			|| $expr instanceof Node\Expr\StaticPropertyFetch
			|| $expr instanceof Node\Expr\NullsafePropertyFetch
			|| $expr instanceof Node\Expr\ArrayDimFetch
			|| $expr instanceof Node\Scalar
			|| $expr instanceof Node\Expr\Isset_
			|| $expr instanceof Node\Expr\Empty_
			|| $expr instanceof Node\Expr\ConstFetch
			|| $expr instanceof Node\Expr\ClassConstFetch;
	}

}

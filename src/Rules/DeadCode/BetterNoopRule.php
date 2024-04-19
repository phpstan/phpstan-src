<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\NoopExpressionNode;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function preg_split;
use function sprintf;

/**
 * @implements Rule<NoopExpressionNode>
 */
class BetterNoopRule implements Rule
{

	public function __construct(private ExprPrinter $exprPrinter)
	{
	}

	public function getNodeType(): string
	{
		return NoopExpressionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$expr = $node->getOriginalExpr();
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

		if ($node->hasAssign()) {
			return [];
		}

		if ($expr instanceof Node\Expr\BinaryOp\BooleanAnd || $expr instanceof Node\Expr\BinaryOp\BooleanOr) {
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
			return [
				RuleErrorBuilder::message('Unused result of ternary operator.')
					->line($expr->getStartLine())
					->identifier('ternary.resultUnused')
					->build(),
			];
		}

		if ($expr instanceof Node\Expr\FuncCall) {
			if ($expr->name instanceof Node\Name) {
				// handled by CallToFunctionStatementWithoutSideEffectsRule
				return [];
			}

			$nameType = $scope->getType($expr->name);
			if (!$nameType->isCallable()->yes()) {
				return [];
			}
		}

		if ($expr instanceof Node\Expr\New_ && $expr->class instanceof Node\Name) {
			// handled by CallToConstructorStatementWithoutSideEffectsRule
			return [];
		}

		if (
			$expr instanceof Node\Expr\NullsafeMethodCall
			|| $expr instanceof Node\Expr\MethodCall
			|| $expr instanceof Node\Expr\StaticCall
		) {
			// handled by *WithoutSideEffectsRule rules
			return [];
		}

		if (
			$expr instanceof Node\Expr\Assign
			|| $expr instanceof Node\Expr\AssignOp
			|| $expr instanceof Node\Expr\AssignRef
		) {
			return [];
		}

		if ($expr instanceof Node\Expr\Closure) {
			return [];
		}

		$exprString = $this->exprPrinter->printExpr($expr);
		$exprStringLines = preg_split('~\R~', $exprString, 2);
		if ($exprStringLines !== false && count($exprStringLines) > 1) {
			$exprString = $exprStringLines[0] . '…';
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Expression "%s" on a separate line does not do anything.',
				$exprString,
			))->line($expr->getStartLine())
				->identifier('expr.resultUnused')
				->build(),
		];
	}

}

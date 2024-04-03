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

	public function __construct(private ExprPrinter $exprPrinter, private bool $better)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Expression::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->better) {
			// disabled in bleeding edge
			return [];
		}
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

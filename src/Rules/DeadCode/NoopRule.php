<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Expression>
 */
class NoopRule implements Rule
{

	public function __construct(private Standard $printer)
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
		if (
			!$expr instanceof Node\Expr\Variable
			&& !$expr instanceof Node\Expr\PropertyFetch
			&& !$expr instanceof Node\Expr\StaticPropertyFetch
			&& !$expr instanceof Node\Expr\NullsafePropertyFetch
			&& !$expr instanceof Node\Expr\ArrayDimFetch
			&& !$expr instanceof Node\Scalar
			&& !$expr instanceof Node\Expr\Isset_
			&& !$expr instanceof Node\Expr\Empty_
			&& !$expr instanceof Node\Expr\ConstFetch
			&& !$expr instanceof Node\Expr\ClassConstFetch
		) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Expression "%s" on a separate line does not do anything.',
				$this->printer->prettyPrintExpr($originalExpr),
			))->line($expr->getLine())
				->identifier('deadCode.noopExpression')
				->metadata([
					'depth' => $node->getAttribute('statementDepth'),
					'order' => $node->getAttribute('statementOrder'),
				])
				->build(),
		];
	}

}

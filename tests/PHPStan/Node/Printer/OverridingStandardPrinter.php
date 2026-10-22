<?php declare(strict_types = 1);

namespace PHPStan\Node\Printer;

use Override;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use function is_string;

final class OverridingStandardPrinter extends Standard
{

	// reached through the dynamic $this->{'p' . $node->getType()}() dispatch
	#[Override]
	protected function pExpr_Variable(Node\Expr\Variable $node): string // phpcs:ignore
	{
		return '$overridden_' . (is_string($node->name) ? $node->name : '?');
	}

	// called directly from the parent's own methods
	#[Override]
	protected function pInfixOp(string $class, Node $leftNode, string $operatorString, Node $rightNode, int $precedence, int $lhsPrecedence): string
	{
		return '<' . parent::pInfixOp($class, $leftNode, $operatorString, $rightNode, $precedence, $lhsPrecedence) . '>';
	}

	#[Override]
	public function prettyPrintExpr(Node\Expr $node): string
	{
		return '{' . parent::prettyPrintExpr($node) . '}';
	}

}

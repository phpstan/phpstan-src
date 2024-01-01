<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Php\PhpVersion;
use function count;
use function version_compare;

class RemoveUnusedCodeByPhpVersionIdVisitor extends NodeVisitorAbstract
{

	public function __construct(private string $phpVersionString)
	{
	}

	public function enterNode(Node $node): Node|int|null
	{
		if (!$node instanceof Node\Stmt\If_) {
			return null;
		}

		if (count($node->elseifs) > 0) {
			return null;
		}

		if ($node->else === null) {
			return null;
		}

		$cond = $node->cond;
		if (
			!$cond instanceof Node\Expr\BinaryOp\Smaller
			&& !$cond instanceof Node\Expr\BinaryOp\SmallerOrEqual
			&& !$cond instanceof Node\Expr\BinaryOp\Greater
			&& !$cond instanceof Node\Expr\BinaryOp\GreaterOrEqual
			&& !$cond instanceof Node\Expr\BinaryOp\Equal
			&& !$cond instanceof Node\Expr\BinaryOp\NotEqual
			&& !$cond instanceof Node\Expr\BinaryOp\Identical
			&& !$cond instanceof Node\Expr\BinaryOp\NotIdentical
		) {
			return null;
		}

		$operator = $cond->getOperatorSigil();
		if ($operator === '===') {
			$operator = '==';
		} elseif ($operator === '!==') {
			$operator = '!=';
		}

		$operands = $this->getOperands($cond->left, $cond->right);
		if ($operands === null) {
			return null;
		}

		$result = version_compare($operands[0], $operands[1], $operator);
		if ($result) {
			// remove else
			$node->cond = new Node\Expr\ConstFetch(new Node\Name('true'));
			$node->else = null;

			return $node;
		}

		// remove if
		$node->cond = new Node\Expr\ConstFetch(new Node\Name('false'));
		$node->stmts = [];

		return $node;
	}

	/**
	 * @return array{string, string}|null
	 */
	private function getOperands(Node\Expr $left, Node\Expr $right): ?array
	{
		if (
			$left instanceof Node\Scalar\Int_
			&& $right instanceof Node\Expr\ConstFetch
			&& $right->name->toString() === 'PHP_VERSION_ID'
		) {
			return [(new PhpVersion($left->value))->getVersionString(), $this->phpVersionString];
		}

		if (
			$right instanceof Node\Scalar\Int_
			&& $left instanceof Node\Expr\ConstFetch
			&& $left->name->toString() === 'PHP_VERSION_ID'
		) {
			return [$this->phpVersionString, (new PhpVersion($right->value))->getVersionString()];
		}

		return null;
	}

}
